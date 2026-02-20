<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class TenantService
{
    public static function current(): ?array
    {
        if (isset($_REQUEST['tenant']) && is_string($_REQUEST['tenant'])) {
            $slug = trim($_REQUEST['tenant']);
        } else {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $parts = explode('.', $host);
            $slug = count($parts) >= 3 ? $parts[0] : APP_DEFAULT_TENANT;
        }

        if ($slug === '') {
            return null;
        }

        $stmt = Database::connection()->prepare('SELECT * FROM tenants WHERE slug = :slug AND ativo = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $tenant = $stmt->fetch();

        return $tenant ?: null;
    }

    public static function tenantId(): ?int
    {
        $tenant = self::current();
        return $tenant ? (int)$tenant['id'] : null;
    }

    public static function config(?int $tenantId = null): array
    {
        $tid = $tenantId ?? self::tenantId();
        if (!$tid) {
            return [
                'nome_prefeitura' => APP_NAME,
                'cor_primaria' => '#198754',
                'logo' => '',
                'texto_rodape' => 'Cata Treco SaaS',
            ];
        }

        $stmt = Database::connection()->prepare('SELECT * FROM configuracoes WHERE tenant_id = :tenant_id LIMIT 1');
        $stmt->execute(['tenant_id' => $tid]);
        $config = $stmt->fetch();

        return $config ?: [
            'nome_prefeitura' => APP_NAME,
            'cor_primaria' => '#198754',
            'logo' => '',
            'texto_rodape' => 'Cata Treco SaaS',
        ];
    }
}
