<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Security;
use App\Models\AuditModel;
use App\Models\DocumentModel;
use App\Models\NewsModel;
use App\Models\SchoolModel;
use App\Models\StockModel;
use App\Services\AuditService;
use App\Services\CacheService;
use App\Services\UploadService;

final class AdminController extends Controller
{
    private CacheService $cache;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    public function dashboard(): void
    {
        Auth::requirePermission('admin.access');
        if (random_int(1, 20) === 1) {
            $this->cache->purgeExpired();
        }
        $schools = (new SchoolModel())->allPublic();
        $stock = (new StockModel())->itemsWithAlerts();
        $this->view('admin/dashboard', [
            'schoolCount' => count($schools),
            'stockAlerts' => array_filter($stock, static fn(array $item): bool => (bool) $item['is_low_stock'] || (bool) $item['near_expiry']),
        ]);
    }

    public function escolas(): void
    {
        Auth::requirePermission('schools.manage');
        $this->view('admin/escolas', ['csrf' => Csrf::token(), 'schools' => (new SchoolModel())->all()]);
    }

    public function salvarEscola(): void
    {
        Auth::requirePermission('schools.manage');
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            exit('CSRF inválido');
        }

        $payload = [
            ':name' => Security::cleanString($_POST['name'] ?? '', 180),
            ':type' => Security::cleanString($_POST['type'] ?? '', 20),
            ':address' => Security::cleanString($_POST['address'] ?? '', 255),
            ':phone' => Security::cleanString($_POST['phone'] ?? '', 30),
            ':director_name' => Security::cleanString($_POST['director_name'] ?? '', 150),
            ':latitude' => (float) ($_POST['latitude'] ?? 0),
            ':longitude' => (float) ($_POST['longitude'] ?? 0),
            ':total_students' => (int) ($_POST['total_students'] ?? 0),
            ':available_slots' => (int) ($_POST['available_slots'] ?? 0),
            ':rooms_count' => (int) ($_POST['rooms_count'] ?? 0),
            ':region' => Security::cleanString($_POST['region'] ?? '', 100),
        ];

        $schoolId = (new SchoolModel())->create($payload);
        AuditService::log('school', $schoolId, 'create', Auth::user()['id'] ?? null, null, $payload);
        $this->cache->invalidateTag('map_public');
        $this->cache->invalidateTag('schools');
        $this->cache->invalidateTag('school_' . $schoolId);
        $this->redirect('admin/escolas');
    }

    public function noticias(): void
    {
        Auth::requirePermission('content.manage');
        $this->view('admin/noticias', ['csrf' => Csrf::token()]);
    }

    public function salvarNoticia(): void
    {
        Auth::requirePermission('content.manage');
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            exit('CSRF inválido');
        }

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $imagePath = (new UploadService())->save($_FILES['image'], 'news');
        }

        $payload = [
            ':title' => Security::cleanString($_POST['title'] ?? '', 200),
            ':slug' => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', (string) ($_POST['title'] ?? 'noticia'))),
            ':category' => Security::cleanString($_POST['category'] ?? '', 80),
            ':summary' => trim((string) ($_POST['summary'] ?? '')),
            ':body' => trim((string) ($_POST['body'] ?? '')),
            ':image_path' => $imagePath,
            ':status' => 'published',
            ':published_at' => date('Y-m-d H:i:s'),
            ':created_by' => Auth::user()['id'] ?? null,
        ];
        $id = (new NewsModel())->create($payload);
        AuditService::log('news', $id, 'create', Auth::user()['id'] ?? null, null, $payload);
        $this->cache->invalidateTag('news');
        $this->cache->invalidateTag('home');
        $this->redirect('admin/noticias');
    }

    public function documentos(): void
    {
        Auth::requirePermission('content.manage');
        $this->view('admin/documentos', ['csrf' => Csrf::token()]);
    }

    public function salvarDocumento(): void
    {
        Auth::requirePermission('content.manage');
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            exit('CSRF inválido');
        }

        $filePath = !empty($_FILES['document']['name']) ? (new UploadService())->save($_FILES['document'], 'documents') : Security::cleanString($_POST['file_path'] ?? '', 255);

        $payload = [
            ':title' => Security::cleanString($_POST['title'] ?? '', 200),
            ':category' => Security::cleanString($_POST['category'] ?? '', 100),
            ':tags' => Security::cleanString($_POST['tags'] ?? '', 255),
            ':year_ref' => (int) ($_POST['year_ref'] ?? date('Y')),
            ':file_path' => $filePath,
            ':is_public' => 1,
            ':created_by' => Auth::user()['id'] ?? null,
        ];
        $id = (new DocumentModel())->create($payload);
        AuditService::log('document', $id, 'create', Auth::user()['id'] ?? null, null, $payload);
        $this->cache->invalidateTag('docs');
        $this->cache->invalidateTag('transparencia');
        $this->redirect('admin/documentos');
    }

    public function estoque(): void
    {
        Auth::requirePermission('inventory.manage');
        $this->view('admin/estoque', ['csrf' => Csrf::token(), 'items' => (new StockModel())->itemsWithAlerts()]);
    }

    public function movimentarEstoque(): void
    {
        Auth::requirePermission('inventory.manage');
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            exit('CSRF inválido');
        }

        $payload = [
            ':stock_item_id' => (int) ($_POST['stock_item_id'] ?? 0),
            ':school_id' => (int) ($_POST['school_id'] ?? 0),
            ':movement_type' => Security::cleanString($_POST['movement_type'] ?? '', 20),
            ':quantity' => (float) ($_POST['quantity'] ?? 0),
            ':notes' => Security::cleanString($_POST['notes'] ?? '', 255),
            ':created_by' => Auth::user()['id'] ?? null,
        ];
        $id = (new StockModel())->move($payload);
        AuditService::log('stock_movement', $id, 'create', Auth::user()['id'] ?? null, null, $payload);
        $this->cache->invalidateTag('menu');
        $this->cache->invalidateTag('map_public');
        $this->redirect('admin/estoque');
    }

    public function auditoria(): void
    {
        Auth::requirePermission('audit.view');
        $filters = [
            'entity' => Security::cleanString($_GET['entity'] ?? '', 80),
            'action' => Security::cleanString($_GET['action'] ?? '', 80),
        ];
        $rows = (new AuditModel())->search($filters);
        $this->view('admin/auditoria', ['rows' => $rows, 'filters' => $filters]);
    }

    public function exportarAuditoriaCsv(): void
    {
        Auth::requirePermission('audit.export');
        $rows = (new AuditModel())->search([
            'entity' => Security::cleanString($_GET['entity'] ?? '', 80),
            'action' => Security::cleanString($_GET['action'] ?? '', 80),
        ]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auditoria.csv"');
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['id', 'usuario', 'acao', 'entidade', 'entity_id', 'ip', 'created_at']);
        foreach ($rows as $row) {
            fputcsv($out, [$row['id'], $row['username'], $row['action'], $row['entity'], $row['entity_id'], $row['ip'], $row['created_at']]);
        }
        fclose($out);
    }
}
