<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Models\ServiceCategory;

class ServiceCategoryController extends Controller
{
    private function model(): ServiceCategory
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        return new ServiceCategory(Database::getConnection($dbConfig));
    }

    public function index(): void
    {
        $categories = $this->model()->all();
        $this->view('categories/index', [
            'title' => 'Categorias',
            'user' => Auth::user(),
            'categories' => $categories,
        ]);
    }

    public function create(): void
    {
        $this->view('categories/form', [
            'title' => 'Nova Categoria',
            'user' => Auth::user(),
            'category' => null,
        ]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $data = [
            'nome' => input('nome'),
            'descricao' => input('descricao'),
            'ativo' => input('ativo', '1') === '1' ? 1 : 0,
        ];

        if (mb_strlen($data['nome']) < 3) {
            flash('error', 'Nome da categoria deve ter ao menos 3 caracteres.');
            redirect('/categorias/criar');
        }

        if ($this->model()->existsByName($data['nome'])) {
            flash('error', 'Já existe uma categoria com esse nome.');
            redirect('/categorias/criar');
        }

        $this->model()->create($data);
        Logger::info('Categoria cadastrada', ['nome' => $data['nome']]);
        flash('success', 'Categoria cadastrada com sucesso.');
        redirect('/categorias');
    }

    public function edit(): void
    {
        $id = (int) input('id', '0');
        $category = $this->model()->find($id);

        if (!$category) {
            flash('error', 'Categoria não encontrada.');
            redirect('/categorias');
        }

        $this->view('categories/form', [
            'title' => 'Editar Categoria',
            'user' => Auth::user(),
            'category' => $category,
        ]);
    }

    public function update(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $category = $this->model()->find($id);

        if (!$category) {
            flash('error', 'Categoria não encontrada.');
            redirect('/categorias');
        }

        $data = [
            'nome' => input('nome'),
            'descricao' => input('descricao'),
            'ativo' => input('ativo', '1') === '1' ? 1 : 0,
        ];

        if (mb_strlen($data['nome']) < 3) {
            flash('error', 'Nome da categoria deve ter ao menos 3 caracteres.');
            redirect('/categorias/editar?id=' . $id);
        }

        if ($this->model()->existsByName($data['nome'], $id)) {
            flash('error', 'Já existe uma categoria com esse nome.');
            redirect('/categorias/editar?id=' . $id);
        }

        $this->model()->update($id, $data);
        Logger::info('Categoria atualizada', ['id' => $id]);
        flash('success', 'Categoria atualizada com sucesso.');
        redirect('/categorias');
    }

    public function toggleStatus(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $category = $this->model()->find($id);

        if (!$category) {
            flash('error', 'Categoria não encontrada.');
            redirect('/categorias');
        }

        $this->model()->toggleStatus($id);
        Logger::info('Categoria alterou status', ['id' => $id]);
        flash('success', 'Status da categoria atualizado.');
        redirect('/categorias');
    }

    public function destroy(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $category = $this->model()->find($id);

        if (!$category) {
            flash('error', 'Categoria não encontrada.');
            redirect('/categorias');
        }

        $this->model()->softDelete($id);
        Logger::warning('Categoria excluída logicamente', ['id' => $id]);
        flash('success', 'Categoria excluída logicamente (inativada).');
        redirect('/categorias');
    }
}
