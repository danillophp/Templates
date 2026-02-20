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

        $this->view('citizen/home', [
            'googleMapsKey' => GOOGLE_MAPS_API_KEY,
            'tenant' => $tenant,
            'tenants' => TenantService::allActive(),
            'tenantWarning' => null,
        ]);
    }


    public function trackPage(): void
    {
        $this->view('citizen/track');
    }

    public function points(): void
    {
        $tenantId = TenantService::tenantId() ?? (int) APP_DEFAULT_TENANT;
        $this->json(['ok' => true, 'data' => (new PointModel())->active($tenantId)]);
    }

    public function store(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $tenantId = TenantService::tenantId() ?? (int) APP_DEFAULT_TENANT;

        $sub = (new SubscriptionModel())->activeWithPlan($tenantId);
        if (!$sub) {
            $this->json(['ok' => false, 'message' => 'Serviço temporariamente indisponível para esta prefeitura.'], 403);
            return;
        }

        $used = (new SubscriptionModel())->requestsInMonth($tenantId);
        if ($used >= (int)$sub['limite_solicitacoes_mes']) {
            $this->json(['ok' => false, 'message' => 'Limite mensal de solicitações atingido. Tente novamente mais tarde.'], 403);
            return;
        }

        try {
            $nome = trim(strip_tags((string)($_POST['full_name'] ?? '')));
            $endereco = trim(strip_tags((string)($_POST['address'] ?? '')));
            $cep = preg_replace('/[^0-9\-]/', '', trim((string)($_POST['cep'] ?? ''))) ?? '';
            $bairro = trim(strip_tags((string)($_POST['district'] ?? 'Não informado')));
            $telefone = preg_replace('/\D+/', '', (string)($_POST['whatsapp'] ?? '')) ?? '';
            $dataSolicitada = trim((string)($_POST['pickup_datetime'] ?? ''));

            if ($nome === '' || $endereco === '' || $cep === '' || $telefone === '' || $dataSolicitada === '') {
                throw new \RuntimeException('Preencha todos os campos obrigatórios.');
            }

            $today = new \DateTimeImmutable('today');
            $requestedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $dataSolicitada);
            if (!$requestedDate || $requestedDate->format('Y-m-d') !== $dataSolicitada) {
                throw new \RuntimeException('Data inválida. Use o calendário para selecionar uma data válida.');
            }
            if ($requestedDate < $today) {
                throw new \RuntimeException('Não é permitido selecionar data de coleta no passado.');
            }

            $dataMysql = $requestedDate->format('Y-m-d');

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
            $this->json([
                'ok' => true,
                'message' => 'Solicitação enviada com sucesso.',
                'protocolo' => $request['protocolo'] ?? '',
                'receipt' => [
                    'nome' => $nome,
                    'endereco' => $endereco,
                    'data_solicitada' => $dataMysql,
                    'telefone' => $telefone,
                    'protocolo' => $request['protocolo'] ?? '',
                    'status' => $request['status'] ?? 'PENDENTE',
                ],
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function track(): void
    {
        $tenantId = TenantService::tenantId() ?? (int) APP_DEFAULT_TENANT;
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
            throw new \RuntimeException('Foto dos Trecos é obrigatória.');
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
