<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\PickupRequest;

class PublicController extends BaseController
{
    public function index(Request $request): void
    {
        View::render('public/home', [
            'pageTitle' => 'CATA TRECO - Solicitação',
            'csrfField' => $this->csrfField(),
        ]);
    }

    public function submit(Request $request): void
    {
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));

        $name = trim((string) $request->input('citizen_name'));
        $address = trim((string) $request->input('address'));
        $cep = trim((string) $request->input('cep'));
        $whatsapp = trim((string) $request->input('whatsapp'));
        $scheduledAt = trim((string) $request->input('scheduled_at'));
        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');
        $consent = (int) $request->input('consent_lgpd', 0);

        if ($name === '' || $address === '' || $whatsapp === '' || $scheduledAt === '' || !$consent) {
            Response::json(['ok' => false, 'message' => 'Preencha os campos obrigatórios e aceite o consentimento LGPD.'], 422);
        }

        $photo = $this->uploadPhoto($_FILES['photo'] ?? null);

        $id = (new PickupRequest())->create([
            'citizen_name' => $name,
            'address' => $address,
            'cep' => $cep,
            'whatsapp' => $whatsapp,
            'photo_path' => $photo,
            'scheduled_at' => $scheduledAt,
            'latitude' => $lat,
            'longitude' => $lng,
            'consent_lgpd' => $consent,
            'ip_address' => $request->ip(),
        ]);

        (new PickupRequest())->addStatusHistory($id, 'PENDENTE', 0, 'Solicitação criada pelo cidadão');
        $this->audit('solicitacao_publica_criada', ['request_id' => $id]);

        Response::json(['ok' => true, 'message' => 'Solicitação registrada com sucesso!', 'id' => $id]);
    }

    private function uploadPhoto(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            return null;
        }

        $filename = 'treco_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        $dir = __DIR__ . '/../../public/assets/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        return '/assets/uploads/' . $filename;
    }
}
