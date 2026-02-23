<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\ErrorHandler;
use App\Middlewares\RateLimitMiddleware;
use App\Models\LogModel;
use App\Models\PointModel;
use App\Models\RequestModel;
use App\Models\SubscriptionModel;
use App\Services\EmailService;
use App\Services\TenantService;

final class CitizenController extends Controller
{
    public function home(): void
    {
        $tenant = TenantService::current();

        $this->view('citizen/home', [
            'tenant' => $tenant,
            'tenants' => TenantService::allActive(),
            'tenantWarning' => null,
            'bairrosPermitidos' => self::BAIRROS_PERMITIDOS,
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

        if (trim((string)($_POST['site_url'] ?? '')) !== '') {
            $this->json(['ok' => false, 'message' => 'Falha de validação do formulário.'], 422);
            return;
        }

        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $rl = RateLimitMiddleware::checkIpForCitizen($ip, 20, 10, 5, 60);
        if (!($rl['allowed'] ?? false)) {
            $this->json(['ok' => false, 'message' => $rl['message'] ?? 'Muitas tentativas.'], 429);
            return;
        }

        $sub = (new SubscriptionModel())->activeWithPlan($tenantId);
        if (!$sub) {
            $this->json(['ok' => false, 'message' => 'Serviço temporariamente indisponível para esta prefeitura.'], 403);
            return;
        }

        try {
            $nome = trim(strip_tags((string)($_POST['full_name'] ?? '')));
            $endereco = trim(strip_tags((string)($_POST['address'] ?? '')));
            $cep = preg_replace('/\D+/', '', trim((string)($_POST['cep'] ?? ''))) ?? '';
            $bairro = trim(strip_tags((string)($_POST['district'] ?? '')));
            $telefone = preg_replace('/\D+/', '', (string)($_POST['whatsapp'] ?? '')) ?? '';
            $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
            $dataSolicitada = trim((string)($_POST['pickup_datetime'] ?? ''));
            $latitude = (float)($_POST['latitude'] ?? 0);
            $longitude = (float)($_POST['longitude'] ?? 0);
            $localizacaoStatus = strtoupper(trim((string)($_POST['localizacao_status'] ?? 'AUTO_OK')));
            $viacepCity = trim((string)($_POST['viacep_city'] ?? ''));
            $viacepUf = strtoupper(trim((string)($_POST['viacep_uf'] ?? '')));

            if ($nome === '' || $endereco === '' || $cep === '' || $telefone === '' || $dataSolicitada === '' || $email === '' || $bairro === '') {
                throw new \RuntimeException('Preencha todos os campos obrigatórios.');
            }

            if (strlen($cep) !== 8) {
                throw new \RuntimeException('CEP inválido. Digite 8 números.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Informe um e-mail válido.');
            }


            if (!in_array($bairro, self::BAIRROS_PERMITIDOS, true)) {
                throw new \RuntimeException('Selecione um bairro válido da lista.');
            }


            $requestedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $dataSolicitada, new \DateTimeZone(APP_TIMEZONE));
            if (!$requestedDate || $requestedDate->format('Y-m-d') !== $dataSolicitada || $requestedDate < new \DateTimeImmutable('today', new \DateTimeZone(APP_TIMEZONE))) {
                throw new \RuntimeException('Data inválida. Selecione uma data atual ou futura.');
            }

            if ((int)$requestedDate->format('N') !== 4) {
                throw new \RuntimeException('Agendamentos apenas às quintas-feiras.');
            }

            if ($latitude === 0.0 || $longitude === 0.0) {
                throw new \RuntimeException('Confirme a localização no mapa antes de enviar.');
            }
            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                throw new \RuntimeException('Coordenadas inválidas. Ajuste o marcador no mapa.');
            }

            if ($viacepCity !== '' || $viacepUf !== '') {
                if ($this->normalize($viacepCity) !== $this->normalize('Santo Antônio do Descoberto') || $viacepUf !== 'GO') {
                    throw new \RuntimeException('Atendimento exclusivo para Santo Antônio do Descoberto - GO.');
                }
            }

            if (!in_array($localizacaoStatus, ['AUTO_OK', 'EMERGENCIA_MANUAL', 'PENDENTE'], true)) {
                $localizacaoStatus = 'PENDENTE';
            }

            $foto = $this->savePhoto($_FILES['photo'] ?? []);
            $id = (new RequestModel())->create([
                'tenant_id' => $tenantId,
                'nome' => $nome,
                'endereco' => $endereco,
                'cep' => $cep,
                'bairro' => $bairro,
                'telefone' => $telefone,
                'email' => $email,
                'foto' => $foto,
                'data_solicitada' => $requestedDate->format('Y-m-d'),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'localizacao_status' => $localizacaoStatus,
            ]);

            $request = (new RequestModel())->find($id, $tenantId);
            (new LogModel())->register($tenantId, $id, null, 'SOLICITACAO_CRIADA', 'Solicitação criada pelo cidadão. Status localização: ' . $localizacaoStatus);

            $receipt = [
                'nome' => $nome,
                'endereco' => $endereco,
                'data_solicitada' => $requestedDate->format('Y-m-d'),
                'telefone' => $telefone,
                'email' => $email,
                'protocolo' => $request['protocolo'] ?? '',
                'status' => $request['status'] ?? 'PENDENTE',
            ];

            $emailStatus = (new EmailService())->sendReceipt($tenantId, $email, $receipt);

            $this->json([
                'ok' => true,
                'message' => 'Solicitação enviada com sucesso.',
                'protocolo' => $request['protocolo'] ?? '',
                'email_delivery' => $emailStatus,
                'receipt' => $receipt,
            ]);
        } catch (\Throwable $e) {
            ErrorHandler::log('CITIZEN_STORE erro: ' . $e->getMessage());
            $message = APP_ENV === 'production' ? 'Não foi possível concluir a solicitação. Verifique os dados e tente novamente.' : $e->getMessage();
            $this->json(['ok' => false, 'message' => $message], 422);
        }
    }

    public function track(): void
    {
        $tenantId = TenantService::tenantId() ?? (int) APP_DEFAULT_TENANT;
        $protocol = trim((string)($_GET['protocol'] ?? ''));
        $phone = preg_replace('/\D+/', '', (string)($_GET['phone'] ?? '')) ?? '';

        if (!$tenantId || ($protocol === '' && $phone === '')) {
            $this->json(['ok' => false, 'message' => 'Informe protocolo ou telefone.'], 422);
            return;
        }

        $row = (new RequestModel())->findByProtocolOrPhone($protocol, $phone, $tenantId);
        if (!$row) {
            $this->json(['ok' => false, 'message' => 'Solicitação não encontrada.'], 404);
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

        $hash = hash('sha256', random_bytes(16) . microtime(true) . ($file['name'] ?? 'upload'));
        $name = $hash . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $name)) {
            throw new \RuntimeException('Falha ao salvar foto.');
        }
        return $name;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return strtr($value, ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);
    }
}
