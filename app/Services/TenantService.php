<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class TenantService
{
    public static function current(): ?array
    {
        try {
            // 1) parâmetro explícito (?tenant=slug ou ?tenant=1)
            if (isset($_REQUEST['tenant']) && is_string($_REQUEST['tenant'])) {
                $tenantParam = trim($_REQUEST['tenant']);
                if ($tenantParam !== '') {
                    $tenant = ctype_digit($tenantParam)
                        ? self::findById((int)$tenantParam)
                        : self::findBySlug($tenantParam);
                    if ($tenant) {
                        return $tenant;
                    }
                }
            }

            // 2) subdomínio, quando existir
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $subdomainSlug = trim((string)$parts[0]);
                if ($subdomainSlug !== '') {
                    $tenant = self::findBySlug($subdomainSlug);
                    if ($tenant) {
                        return $tenant;
                    }
                }
            }

            // 3) tenant padrão por ID (sem dependência de subdomínio)
            $defaultTenant = self::findById((int) APP_DEFAULT_TENANT);
            if ($defaultTenant) {
                return $defaultTenant;
            }

            // 4) fallback final
            return self::firstActive();
        } catch (\Throwable $e) {
            error_log('[CataTreco][TENANT] ' . $e->getMessage());
            return null;
        }
    }

    public static function tenantId(): ?int
    {
        $tenant = self::current();
        return $tenant ? (int)$tenant['id'] : null;
    }

    public static function allActive(): array
    {
        try {
            return Database::connection()->query('SELECT id, nome, slug FROM tenants WHERE ativo = 1 ORDER BY nome')->fetchAll();
        } catch (\Throwable $e) {
            error_log('[CataTreco][TENANT_LIST] ' . $e->getMessage());
            return [];
        }
    }

    public static function config(?int $tenantId = null): array
    {
        $tid = $tenantId ?? self::tenantId();
        if (!$tid) {
            return self::defaultConfig();
        }

        try {
            $stmt = Database::connection()->prepare('SELECT * FROM configuracoes WHERE tenant_id = :tenant_id LIMIT 1');
            $stmt->execute(['tenant_id' => $tid]);
            $config = $stmt->fetch();
            return $config ?: self::defaultConfig();
        } catch (\Throwable $e) {
            error_log('[CataTreco][TENANT_CONFIG] ' . $e->getMessage());
            return self::defaultConfig();
        }
    }

    private static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tenants WHERE slug = :slug AND ativo = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $tenant = $stmt->fetch();
        return $tenant ?: null;
    }

    private static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = Database::connection()->prepare('SELECT * FROM tenants WHERE id = :id AND ativo = 1 LIMIT 1');
        $stmt->execute(['id' => $id]);
        $tenant = $stmt->fetch();
        return $tenant ?: null;
    }

    private static function firstActive(): ?array
    {
        $stmt = Database::connection()->query('SELECT * FROM tenants WHERE ativo = 1 ORDER BY id ASC LIMIT 1');
        $tenant = $stmt->fetch();
        return $tenant ?: null;
    }

    private static function defaultConfig(): array
    {
        return [
            'nome_prefeitura' => 'Prefeitura Municipal',
            'cor_primaria' => '#0b8f62',
            'logo' => '',
            'texto_rodape' => 'Cata Treco',
        ];
    }
}
