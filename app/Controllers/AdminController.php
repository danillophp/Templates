<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\DocumentModel;
use App\Models\NewsModel;
use App\Models\SchoolModel;
use App\Models\StockModel;
use App\Services\AuditService;

final class AdminController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole(ROLES);
        $schools = (new SchoolModel())->allPublic();
        $stock = (new StockModel())->itemsWithAlerts();
        $this->view('admin/dashboard', [
            'schoolCount' => count($schools),
            'stockAlerts' => array_filter($stock, static fn(array $item): bool => (bool) $item['is_low_stock'] || (bool) $item['near_expiry']),
        ]);
    }

    public function escolas(): void
    {
        Auth::requireRole(['super_admin', 'secretaria', 'gestor']);
        $this->view('admin/escolas', ['csrf' => Csrf::token(), 'schools' => (new SchoolModel())->all()]);
    }

    public function salvarEscola(): void
    {
        Auth::requireRole(['super_admin', 'secretaria', 'gestor']);
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            exit('CSRF inválido');
        }

        $schoolId = (new SchoolModel())->create([
            ':name' => trim((string) $_POST['name']),
            ':type' => trim((string) $_POST['type']),
            ':address' => trim((string) $_POST['address']),
            ':phone' => trim((string) $_POST['phone']),
            ':director_name' => trim((string) $_POST['director_name']),
            ':latitude' => (float) $_POST['latitude'],
            ':longitude' => (float) $_POST['longitude'],
            ':total_students' => (int) $_POST['total_students'],
            ':available_slots' => (int) $_POST['available_slots'],
            ':rooms_count' => (int) $_POST['rooms_count'],
            ':region' => trim((string) $_POST['region']),
        ]);
        AuditService::log('school', $schoolId, 'create', Auth::user()['id'] ?? null, ['name' => $_POST['name']]);
        $this->redirect('admin/escolas');
    }

    public function noticias(): void
    {
        Auth::requireRole(['super_admin', 'secretaria', 'conteudo']);
        $this->view('admin/noticias', ['csrf' => Csrf::token()]);
    }

    public function salvarNoticia(): void
    {
        Auth::requireRole(['super_admin', 'secretaria', 'conteudo']);
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            exit('CSRF inválido');
        }

        $id = (new NewsModel())->create([
            ':title' => trim((string) $_POST['title']),
            ':slug' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $_POST['title'])),
            ':category' => trim((string) $_POST['category']),
            ':summary' => trim((string) $_POST['summary']),
            ':body' => trim((string) $_POST['body']),
            ':image_path' => null,
            ':status' => 'published',
            ':published_at' => date('Y-m-d H:i:s'),
            ':created_by' => Auth::user()['id'] ?? null,
        ]);
        AuditService::log('news', $id, 'create', Auth::user()['id'] ?? null);
        $this->redirect('admin/noticias');
    }

    public function documentos(): void
    {
        Auth::requireRole(['super_admin', 'secretaria', 'conteudo']);
        $this->view('admin/documentos', ['csrf' => Csrf::token()]);
    }

    public function salvarDocumento(): void
    {
        Auth::requireRole(['super_admin', 'secretaria', 'conteudo']);
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            exit('CSRF inválido');
        }

        $id = (new DocumentModel())->create([
            ':title' => trim((string) $_POST['title']),
            ':category' => trim((string) $_POST['category']),
            ':tags' => trim((string) $_POST['tags']),
            ':year_ref' => (int) $_POST['year_ref'],
            ':file_path' => trim((string) $_POST['file_path']),
            ':is_public' => 1,
            ':created_by' => Auth::user()['id'] ?? null,
        ]);
        AuditService::log('document', $id, 'create', Auth::user()['id'] ?? null);
        $this->redirect('admin/documentos');
    }

    public function estoque(): void
    {
        Auth::requireRole(['super_admin', 'secretaria', 'gestor', 'estoque']);
        $this->view('admin/estoque', ['csrf' => Csrf::token(), 'items' => (new StockModel())->itemsWithAlerts()]);
    }

    public function movimentarEstoque(): void
    {
        Auth::requireRole(['super_admin', 'secretaria', 'gestor', 'estoque']);
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            exit('CSRF inválido');
        }

        $id = (new StockModel())->move([
            ':stock_item_id' => (int) $_POST['stock_item_id'],
            ':school_id' => (int) $_POST['school_id'],
            ':movement_type' => trim((string) $_POST['movement_type']),
            ':quantity' => (float) $_POST['quantity'],
            ':notes' => trim((string) $_POST['notes']),
            ':created_by' => Auth::user()['id'] ?? null,
        ]);
        AuditService::log('stock_movement', $id, 'create', Auth::user()['id'] ?? null);
        $this->redirect('admin/estoque');
    }
}
