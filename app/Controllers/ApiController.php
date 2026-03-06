<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\NewsModel;
use App\Models\SchoolModel;
use App\Models\StockModel;
use App\Services\CacheService;

final class ApiController extends Controller
{
    private CacheService $cache;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    public function publicSchools(): void
    {
        $type = trim((string) ($_GET['type'] ?? ''));
        $region = trim((string) ($_GET['region'] ?? ''));
        $minSlots = (int) ($_GET['min_slots'] ?? 0);

        $key = 'api_schools_' . md5(json_encode([$type, $region, $minSlots]));
        $model = new SchoolModel();
        $lastModified = $model->lastModifiedPublic();

        $geojson = $this->cache->remember($key, 180, ['map_public', 'schools'], function () use ($model, $type, $region, $minSlots): array {
            $rows = array_filter($model->allPublic(), static function (array $r) use ($type, $region, $minSlots): bool {
                if ($type !== '' && $r['type'] !== $type) {
                    return false;
                }
                if ($region !== '' && strcasecmp((string) $r['region'], $region) !== 0) {
                    return false;
                }
                return (int) $r['available_slots'] >= $minSlots;
            });

            $features = array_map(static function (array $s): array {
                return [
                    'type' => 'Feature',
                    'geometry' => ['type' => 'Point', 'coordinates' => [(float) $s['longitude'], (float) $s['latitude']]],
                    'properties' => [
                        'id' => (int) $s['id'],
                        'name' => $s['name'],
                        'type' => $s['type'],
                        'address' => $s['address'],
                        'phone' => $s['phone'],
                        'director_name' => $s['director_name'],
                        'total_students' => (int) $s['total_students'],
                        'available_slots' => (int) $s['available_slots'],
                        'rooms_count' => (int) $s['rooms_count'],
                        'region' => $s['region'],
                    ],
                ];
            }, array_values($rows));

            return ['type' => 'FeatureCollection', 'features' => $features];
        });

        $etag = hash('sha256', json_encode($geojson));
        if ($this->applyHttpCacheHeaders($etag, $lastModified)) {
            return;
        }

        $this->json($geojson);
    }

    public function publicNews(): void
    {
        $model = new NewsModel();
        $lastModified = $model->lastModifiedPublic();
        $data = $this->cache->remember('api_news', 120, ['news', 'home'], static fn(): array => $model->latestPublic());

        $etag = hash('sha256', json_encode($data));
        if ($this->applyHttpCacheHeaders($etag, $lastModified)) {
            return;
        }

        $this->json(['data' => $data]);
    }

    public function adminStockAlerts(): void
    {
        Auth::requireRole(['super_admin', 'secretaria', 'gestor', 'estoque']);
        $alerts = array_filter((new StockModel())->itemsWithAlerts(), static fn(array $item): bool => (bool) $item['is_low_stock'] || (bool) $item['near_expiry']);
        $this->json(['data' => array_values($alerts)]);
    }
}
