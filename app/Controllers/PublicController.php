<?php
namespace App\Controllers;

use App\Models\PointModel;
use App\Models\RequestModel;
use App\Models\NotificationModel;
use App\Services\GeoService;

class PublicController
{
    public function home(): void
    {
        $points = (new PointModel())->allActive();
        require __DIR__ . '/../../resources/views/public/home.php';
    }

    public function submitRequest(): void
    {
        $data = [
            'nome' => trim($_POST['nome'] ?? ''),
            'email' => filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
            'telefone' => preg_replace('/\D+/', '', $_POST['telefone'] ?? ''),
            'endereco' => trim($_POST['endereco'] ?? ''),
            'cep' => preg_replace('/\D+/', '', $_POST['cep'] ?? ''),
            'data_agendada' => $_POST['data_agendada'] ?? '',
            'latitude' => (float)($_POST['latitude'] ?? 0),
            'longitude' => (float)($_POST['longitude'] ?? 0),
            'cidade' => trim($_POST['cidade'] ?? ''),
            'uf' => trim($_POST['uf'] ?? ''),
        ];
        if (!$data['nome'] || !$data['email'] || !$data['telefone'] || !$data['endereco'] || !$data['data_agendada']) {
            exit('Dados obrigatórios ausentes.');
        }
        if (strtotime($data['data_agendada']) < strtotime(date('Y-m-d'))) exit('Data inválida.');
        if (!((new GeoService())->cidadeUfValida($data['cidade'], $data['uf']))) exit('CEP fora do município atendido.');
        if ($data['latitude'] < -90 || $data['latitude'] > 90 || $data['longitude'] < -180 || $data['longitude'] > 180) exit('Coordenadas inválidas.');

        $fotoPath = null;
        if (!empty($_FILES['foto']['tmp_name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && $_FILES['foto']['size'] <= 5 * 1024 * 1024) {
                $name = 'sol_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = __DIR__ . '/../../uploads/' . $name;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) $fotoPath = 'uploads/' . $name;
            }
        }

        $id = (new RequestModel())->create([
            'nome' => htmlspecialchars($data['nome']),
            'email' => $data['email'],
            'telefone' => $data['telefone'],
            'endereco' => htmlspecialchars($data['endereco']),
            'cep' => $data['cep'],
            'data_agendada' => $data['data_agendada'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'foto_path' => $fotoPath,
            'status' => 'PENDENTE'
        ]);
        $req = (new RequestModel())->find($id);
        (new NotificationModel())->createNewSchedule($id, ['protocolo' => $req['protocolo'], 'nome' => $req['nome']]);
        $_SESSION['comprovante'] = $req;
        header('Location: ' . $_ENV['APP_BASE_PATH'] . '/comprovante');
    }

    public function comprovante(): void
    {
        $req = $_SESSION['comprovante'] ?? null;
        require __DIR__ . '/../../resources/views/public/comprovante.php';
    }

    public function protocolo(): void
    {
        $results = [];
        if (!empty($_GET['q'])) {
            $results = (new RequestModel())->findByProtocolOrPhone(trim($_GET['q']));
        }
        require __DIR__ . '/../../resources/views/public/protocolo.php';
    }
}
