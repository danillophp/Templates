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
            $photo = $this->savePhoto($_FILES['photo'] ?? []);
            $pickup = date('Y-m-d H:i:s', strtotime($_POST['pickup_datetime'] ?? ''));
            $address = trim($_POST['address'] ?? '');
            $district = trim($_POST['district'] ?? 'Não informado');
            $model = new RequestModel();
            $id = $model->create([
                'full_name' => trim($_POST['full_name'] ?? ''),
                'address' => $address,
                'cep' => trim($_POST['cep'] ?? ''),
                'district' => $district,
                'whatsapp' => trim($_POST['whatsapp'] ?? ''),
                'photo' => $photo,
                'pickup' => $pickup,
                'lat' => (float)($_POST['latitude'] ?? 0),
                'lng' => (float)($_POST['longitude'] ?? 0),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);
            (new LogModel())->register($id, null, 'CIDADAO', 'CRIADA', 'Solicitação criada.');
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
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new \RuntimeException('Formato inválido.');
        }
        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0775, true);
        }
        $name = uniqid('treco_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $name)) {
            throw new \RuntimeException('Falha no upload.');
        }
        return $name;
    }
}
