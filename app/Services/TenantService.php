<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class TenantService
{
    public static function current(): ?array
    {
        $candidates = [];

        if (isset($_REQUEST['tenant']) && is_string($_REQUEST['tenant'])) {
            $requestSlug = trim($_REQUEST['tenant']);
            if ($requestSlug !== '') {
                $candidates[] = $requestSlug;
            }
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $subdomainSlug = trim((string)$parts[0]);
            if ($subdomainSlug !== '') {
                $candidates[] = $subdomainSlug;
            }
        }

        if (APP_DEFAULT_TENANT !== '') {
            $candidates[] = APP_DEFAULT_TENANT;
        }

        $candidates = array_values(array_unique($candidates));
        foreach ($candidates as $slug) {
            $tenant = self::findBySlug($slug);
            if ($tenant) {
                return $tenant;
            }
        }

        return self::firstActive();
    }

    public static function tenantId(): ?int
    {
        $tenant = self::current();
        return $tenant ? (int)$tenant['id'] : null;
    }

    public static function allActive(): array
    {
        return Database::connection()->query('SELECT id, nome, slug FROM tenants WHERE ativo = 1 ORDER BY nome')->fetchAll();
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

    private static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tenants WHERE slug = :slug AND ativo = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $tenant = $stmt->fetch();
        return $tenant ?: null;
    }

    private static function firstActive(): ?array
    {
        $stmt = Database::connection()->query('SELECT * FROM tenants WHERE ativo = 1 ORDER BY id ASC LIMIT 1');
        $tenant = $stmt->fetch();
        return $tenant ?: null;
    }
}
