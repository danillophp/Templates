<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\PointModel;
use App\Models\RequestModel;
use App\Models\SubscriptionModel;
use App\Services\TenantService;

final class CitizenController extends Controller
{
    public function home(): void
    {
        $tenant = TenantService::current();
        if (!$tenant) {
            http_response_code(404);
            echo 'Prefeitura não encontrada.';
            return;
        }

        $this->view('citizen/home', [
            'googleMapsKey' => GOOGLE_MAPS_API_KEY,
            'tenant' => $tenant,
        ]);
    }

    public function points(): void
    {
        $tenantId = TenantService::tenantId();
        if (!$tenantId) {
            $this->json(['ok' => false, 'message' => 'Tenant inválido.'], 404);
            return;
        }
        $this->json(['ok' => true, 'data' => (new PointModel())->active($tenantId)]);
    }

    public function store(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $tenantId = TenantService::tenantId();
        if (!$tenantId) {
            $this->json(['ok' => false, 'message' => 'Tenant inválido.'], 404);
            return;
        }

        $sub = (new SubscriptionModel())->activeWithPlan($tenantId);
        if (!$sub) {
            $this->json(['ok' => false, 'message' => 'Prefeitura sem assinatura ativa.'], 403);
            return;
        }

        $used = (new SubscriptionModel())->requestsInMonth($tenantId);
        if ($used >= (int)$sub['limite_solicitacoes_mes']) {
            $this->json(['ok' => false, 'message' => 'Limite mensal de solicitações do plano atingido.'], 403);
            return;
        }

        try {
            $nome = trim((string)($_POST['full_name'] ?? ''));
            $endereco = trim((string)($_POST['address'] ?? ''));
            $cep = trim((string)($_POST['cep'] ?? ''));
            $bairro = trim((string)($_POST['district'] ?? 'Não informado'));
            $telefone = preg_replace('/\D+/', '', (string)($_POST['whatsapp'] ?? '')) ?? '';
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
                'tenant_id' => $tenantId,
                'nome' => $nome,
                'endereco' => $endereco,
                'cep' => $cep,
                'bairro' => $bairro,
                'telefone' => $telefone,
                'foto' => $foto,
                'data_solicitada' => $dataMysql,
                'latitude' => (float)($_POST['latitude'] ?? 0),
                'longitude' => (float)($_POST['longitude'] ?? 0),
            ]);

            $request = (new RequestModel())->find($id, $tenantId);
            (new LogModel())->register($tenantId, $id, null, 'SOLICITACAO_CRIADA', 'Solicitação criada pelo cidadão.');
            $this->json(['ok' => true, 'message' => 'Solicitação enviada com sucesso.', 'protocolo' => $request['protocolo'] ?? '']);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function track(): void
    {
        $tenantId = TenantService::tenantId();
        $protocol = trim((string)($_GET['protocol'] ?? ''));
        $phone = preg_replace('/\D+/', '', (string)($_GET['phone'] ?? '')) ?? '';

        if (!$tenantId || $protocol === '' || $phone === '') {
            $this->json(['ok' => false, 'message' => 'Informe protocolo e telefone.'], 422);
            return;
        }

        $row = (new RequestModel())->findByProtocol($protocol, $phone, $tenantId);
        if (!$row) {
            $this->json(['ok' => false, 'message' => 'Protocolo não encontrado.'], 404);
            return;
        }

        $this->json(['ok' => true, 'data' => $row]);
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
