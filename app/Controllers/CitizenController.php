<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\RequestModel;

final class CitizenController extends Controller
{
    public function home(): void
    {
        $this->view('citizen/home');
    }

    public function store(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        try {
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));
            $cep = trim((string)($_POST['cep'] ?? ''));
            $district = trim((string)($_POST['district'] ?? 'Não informado'));
            $whatsapp = trim((string)($_POST['whatsapp'] ?? ''));
            $pickupRaw = trim((string)($_POST['pickup_datetime'] ?? ''));
            $consent = (int)($_POST['consent'] ?? 0) === 1;

            if ($fullName === '' || $address === '' || $cep === '' || $whatsapp === '' || $pickupRaw === '' || !$consent) {
                throw new \RuntimeException('Preencha todos os campos obrigatórios e aceite o consentimento LGPD.');
            }

            $pickup = date('Y-m-d H:i:s', strtotime($pickupRaw));
            if ($pickup === '1970-01-01 00:00:00') {
                throw new \RuntimeException('Data/hora de coleta inválida.');
            }
            if (strtotime($pickup) < time()) {
                throw new \RuntimeException('Não é permitido agendar coletas em data/hora passada.');
            }

            $photo = $this->savePhoto($_FILES['photo'] ?? []);
            $model = new RequestModel();
            $id = $model->create([
                'full_name' => $fullName,
                'address' => $address,
                'cep' => $cep,
                'district' => $district,
                'whatsapp' => $whatsapp,
                'photo' => $photo,
                'pickup' => $pickup,
                'lat' => (float)($_POST['latitude'] ?? 0),
                'lng' => (float)($_POST['longitude'] ?? 0),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            (new LogModel())->register($id, null, 'CIDADAO', 'CRIADA', 'Solicitação criada pelo cidadão.');
            $this->json(['ok' => true, 'message' => 'Solicitação enviada com sucesso!']);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function savePhoto(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Foto obrigatória.');
        }
        if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
            throw new \RuntimeException('Imagem maior que 5MB.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'] ?? '');
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('Formato inválido. Envie JPG, PNG ou WEBP.');
        }

        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0775, true);
        }

        $name = uniqid('treco_', true) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $name)) {
            throw new \RuntimeException('Falha no upload da imagem.');
        }
        return $name;
    }
}
