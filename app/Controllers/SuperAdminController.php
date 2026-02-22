<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\TenantModel;

final class SuperAdminController extends Controller
{
    private function guard(): void
    {
        if (!Auth::check() || !Auth::is('super_admin')) {
            $this->redirect('/?r=auth/login');
        }
    }

    public function dashboard(): void
    {
        $this->guard();
        $tenantModel = new TenantModel();
        $this->view('superadmin/dashboard', [
            'metrics' => $tenantModel->globalMetrics(),
            'tenants' => $tenantModel->all(),
            'csrf' => Csrf::token(),
        ]);
    }

    public function createTenant(): void
    {
        $this->guard();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $nome = trim((string)($_POST['nome'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $dominio = trim((string)($_POST['dominio'] ?? ''));

        if ($nome === '' || $slug === '' || $dominio === '') {
            $this->json(['ok' => false, 'message' => 'Preencha todos os campos.'], 422);
            return;
        }

        $id = (new TenantModel())->create([
            'nome' => $nome,
            'slug' => $slug,
            'dominio' => $dominio,
        ]);

        $this->json(['ok' => true, 'message' => 'Prefeitura criada com sucesso.', 'tenant_id' => $id]);
    }
}
