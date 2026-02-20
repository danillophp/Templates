<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\PointModel;
use App\Models\RequestModel;

final class CitizenController extends Controller
{
    public function home(): void
    {
        $this->view('citizen/home', [
            'googleMapsKey' => GOOGLE_MAPS_API_KEY,
        ]);
    }

    public function points(): void
    {
        $this->json(['ok' => true, 'data' => (new PointModel())->active()]);
    }

    public function store(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        try {
            $nome = trim((string)($_POST['full_name'] ?? ''));
            $endereco = trim((string)($_POST['address'] ?? ''));
            $cep = trim((string)($_POST['cep'] ?? ''));
            $telefone = trim((string)($_POST['whatsapp'] ?? ''));
            $dataSolicitada = trim((string)($_POST['pickup_datetime'] ?? ''));

            if ($nome === '' || $endereco === '' || $cep === '' || $telefone === '' || $dataSolicitada === '') {
                throw new \RuntimeException('Preencha todos os campos obrigatórios.');
            }

            $dataMysql = date('Y-m-d H:i:s', strtotime($dataSolicitada));
            if ($dataMysql === '1970-01-01 00:00:00') {
                throw new \RuntimeException('Data inválida.');
            }

            $foto = $this->savePhoto($_FILES['photo'] ?? []);

            $id = (new RequestModel())->create([
                'nome' => $nome,
                'endereco' => $endereco,
                'cep' => $cep,
                'telefone' => $telefone,
                'foto' => $foto,
                'data_solicitada' => $dataMysql,
                'latitude' => (float)($_POST['latitude'] ?? 0),
                'longitude' => (float)($_POST['longitude'] ?? 0),
            ]);

            (new LogModel())->register($id, null, 'Solicitação criada pelo cidadão.');
            $this->json(['ok' => true, 'message' => 'Solicitação enviada com sucesso.']);
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
            throw new \RuntimeException('Formato inválido. Use JPG, PNG ou WEBP.');
        }

        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0775, true);
        }

        $name = uniqid('treco_', true) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $name)) {
            throw new \RuntimeException('Falha ao salvar foto.');
        }

        return $name;
    }
}
