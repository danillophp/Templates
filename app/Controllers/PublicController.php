<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\PointModel;
use App\Models\RequestModel;
use App\Models\NotificationModel;
use App\Services\EmailService;
use App\Services\GeoService;

class PublicController extends Controller
{
    public function home(): void
    {
        $points = (new PointModel())->allActive();
        $this->view('public/home', ['points' => $points, 'csrf' => \App\Core\Csrf::token()]);
    }

    public function storeRequest(): void
    {
        \App\Middlewares\CsrfMiddleware::handle();
        $data = [
            'nome' => trim($this->input('nome')),
            'email' => filter_var($this->input('email'), FILTER_VALIDATE_EMAIL),
            'telefone_whatsapp' => trim($this->input('telefone_whatsapp')),
            'endereco' => trim($this->input('endereco')),
            'cep' => trim($this->input('cep')),
            'data_agendada' => $this->input('data_agendada'),
            'latitude' => (float)$this->input('latitude'),
            'longitude' => (float)$this->input('longitude'),
            'foto_path' => null,
        ];

        if (!$data['email']) { die('Email inválido'); }
        if (strtotime($data['data_agendada']) < strtotime(date('Y-m-d'))) { die('Data inválida'); }

        $geo = (new GeoService())->validateCepLocation($data['cep']);
        if (abs($data['latitude'] - $geo['lat']) > 0.3 || abs($data['longitude'] - $geo['lng']) > 0.3) {
            die('Localização inconsistente com CEP');
        }

        if (!empty($_FILES['foto']['name'])) {
            $tmp = $_FILES['foto']['tmp_name'];
            $mime = mime_content_type($tmp);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                die('Arquivo inválido');
            }
            if ($_FILES['foto']['size'] > 5 * 1024 * 1024) { die('Arquivo muito grande'); }
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $name = 'foto_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = __DIR__ . '/../../uploads/' . $name;
            move_uploaded_file($tmp, $dest);
            $data['foto_path'] = 'uploads/' . $name;
        }

        $requestModel = new RequestModel();
        $id = $requestModel->create($data);
        $request = $requestModel->find($id);

        (new NotificationModel())->create($id, ['protocolo' => $request['protocolo'], 'nome' => $request['nome']]);
        (new EmailService())->send($request['email'], 'Comprovante Cata Treco', 'Seu protocolo: ' . $request['protocolo']);

        $this->redirect('/comprovante?id=' . $id);
    }

    public function comprovante(): void
    {
        $id = (int)$this->input('id');
        $request = (new RequestModel())->find($id);
        $this->view('public/comprovante', ['request' => $request]);
    }

    public function protocolo(): void
    {
        $results = [];
        if (!empty($_GET['q']) && !empty($_GET['tipo'])) {
            $m = new RequestModel();
            if ($_GET['tipo'] === 'protocolo') {
                $one = $m->byProtocol(trim($_GET['q']));
                $results = $one ? [$one] : [];
            } else {
                $results = $m->byPhone(trim($_GET['q']));
            }
        }
        $this->view('public/protocolo', ['results' => $results]);
    }
}
