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
        $this->view('categories/index', ['title' => 'Categorias', 'user' => Auth::user(), 'categories' => $categories]);
    }

    public function create(): void
    {
        $this->view('categories/form', ['title' => 'Nova Categoria', 'user' => Auth::user(), 'category' => null]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $data = ['nome' => input('nome'), 'descricao' => input('descricao'), 'ativo' => input('ativo', '1') === '1' ? 1 : 0];

        if ($data['nome'] === '') {
            flash('error', 'Nome da categoria é obrigatório.');
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

        $this->view('categories/form', ['title' => 'Editar Categoria', 'user' => Auth::user(), 'category' => $category]);
    }

    public function update(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $data = ['nome' => input('nome'), 'descricao' => input('descricao'), 'ativo' => input('ativo', '1') === '1' ? 1 : 0];

        $this->model()->update($id, $data);
        Logger::info('Categoria atualizada', ['id' => $id]);
        flash('success', 'Categoria atualizada com sucesso.');
        redirect('/categorias');
    }

    public function destroy(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $this->model()->delete($id);
        Logger::warning('Categoria removida', ['id' => $id]);
        flash('success', 'Categoria removida com sucesso.');
        redirect('/categorias');
    }
}
