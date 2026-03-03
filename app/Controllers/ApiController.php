<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\NewsModel;
use App\Models\SchoolModel;
use App\Models\StockModel;

final class ApiController extends Controller
{
    public function publicSchools(): void
    {
        $this->json(['data' => (new SchoolModel())->allPublic()]);
    }

    public function publicNews(): void
    {
        $this->json(['data' => (new NewsModel())->latestPublic()]);
    }

    public function adminStockAlerts(): void
    {
        Auth::requireRole(['super_admin', 'secretaria', 'gestor', 'estoque']);
        $alerts = array_filter((new StockModel())->itemsWithAlerts(), static fn(array $item): bool => (bool) $item['is_low_stock'] || (bool) $item['near_expiry']);
        $this->json(['data' => array_values($alerts)]);
    }
}
