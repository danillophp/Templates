<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\PointModel;
use App\Models\RequestModel;
use App\Models\SubscriptionModel;
use App\Services\EmailService;
use App\Services\TenantService;

final class CitizenController extends Controller
{
    private const CITY_ALLOWED = 'santo antônio do descoberto';
    private const STATE_ALLOWED = 'goiás';
    private const COUNTRY_ALLOWED = 'brasil';

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

        try {
            $nome = trim(strip_tags((string)($_POST['full_name'] ?? '')));
            $endereco = trim(strip_tags((string)($_POST['address'] ?? '')));
            $cep = preg_replace('/[^0-9\-]/', '', trim((string)($_POST['cep'] ?? ''))) ?? '';
            $bairro = trim(strip_tags((string)($_POST['district'] ?? 'Não informado')));
            $telefone = preg_replace('/\D+/', '', (string)($_POST['whatsapp'] ?? '')) ?? '';
            $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
            $dataSolicitada = trim((string)($_POST['pickup_datetime'] ?? ''));
            $latitude = (float)($_POST['latitude'] ?? 0);
            $longitude = (float)($_POST['longitude'] ?? 0);

            if ($nome === '' || $endereco === '' || $cep === '' || $telefone === '' || $dataSolicitada === '' || $email === '') {
                throw new \RuntimeException('Preencha todos os campos obrigatórios.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Informe um e-mail válido.');
            }

            $requestedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $dataSolicitada);
            if (!$requestedDate || $requestedDate->format('Y-m-d') !== $dataSolicitada || $requestedDate < new \DateTimeImmutable('today')) {
                throw new \RuntimeException('Data inválida. Selecione uma data atual ou futura.');
            }

            if ($latitude === 0.0 || $longitude === 0.0) {
                throw new \RuntimeException('Confirme a localização no mapa antes de enviar.');
            }

            $geoValidation = $this->validateLocation($latitude, $longitude);
            if (!$geoValidation['ok']) {
                throw new \RuntimeException($geoValidation['message']);
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
            ]);

            $request = (new RequestModel())->find($id, $tenantId);
            (new LogModel())->register($tenantId, $id, null, 'SOLICITACAO_CRIADA', 'Solicitação criada pelo cidadão.');

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
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
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

    private function validateLocation(float $latitude, float $longitude): array
    {
        $url = sprintf(
            'https://nominatim.openstreetmap.org/reverse?format=json&lat=%s&lon=%s&zoom=18&addressdetails=1&accept-language=pt-BR',
            rawurlencode((string)$latitude),
            rawurlencode((string)$longitude)
        );

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => "User-Agent: CataTreco/1.0\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return ['ok' => false, 'message' => 'Não foi possível validar localização no momento.'];
        }

        $json = json_decode($raw, true);
        if (!is_array($json) || empty($json['address'])) {
            return ['ok' => false, 'message' => 'Localização inválida no mapa.'];
        }

        $address = $json['address'];
        $city = $this->normalize((string)($address['city'] ?? $address['town'] ?? $address['municipality'] ?? $address['county'] ?? ''));
        $state = $this->normalize((string)($address['state'] ?? ''));
        $country = $this->normalize((string)($address['country'] ?? ''));

        if ($city !== $this->normalize(self::CITY_ALLOWED)
            || $state !== $this->normalize(self::STATE_ALLOWED)
            || $country !== $this->normalize(self::COUNTRY_ALLOWED)) {
            return ['ok' => false, 'message' => 'Atendemos somente Santo Antônio do Descoberto - GO, Brasil.'];
        }

        return ['ok' => true];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return strtr($value, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);
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
