<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Models\Service;
use App\Models\ServiceCategory;

class ServiceController extends Controller
{
    private function model(): Service
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        return new Service(Database::getConnection($dbConfig));
    }

    private function categoryModel(): ServiceCategory
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        return new ServiceCategory(Database::getConnection($dbConfig));
    }

    public function index(): void
    {
        $services = $this->model()->all();
        $this->view('services/index', ['title' => 'Serviços', 'user' => Auth::user(), 'services' => $services]);
    }

    public function create(): void
    {
        $categories = $this->categoryModel()->all();
        $this->view('services/form', ['title' => 'Novo Serviço', 'user' => Auth::user(), 'service' => null, 'categories' => $categories]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $data = [
            'categoria_id' => (int) input('categoria_id', '0'),
            'nome' => input('nome'),
            'descricao' => input('descricao'),
            'valor_total' => (float) input('valor_total', '0'),
            'duracao_minutos' => (int) input('duracao_minutos', '60'),
            'ativo' => input('ativo', '1') === '1' ? 1 : 0,
        ];

        if ($data['categoria_id'] <= 0 || $data['nome'] === '' || $data['valor_total'] <= 0) {
            flash('error', 'Preencha categoria, nome e valor corretamente.');
            redirect('/servicos/criar');
        }

        $this->model()->create($data);
        Logger::info('Serviço cadastrado', ['nome' => $data['nome']]);
        flash('success', 'Serviço cadastrado com sucesso.');
        redirect('/servicos');
    }

    public function edit(): void
    {
        $id = (int) input('id', '0');
        $service = $this->model()->find($id);
        if (!$service) {
            flash('error', 'Serviço não encontrado.');
            redirect('/servicos');
        }

        $categories = $this->categoryModel()->all();
        $this->view('services/form', ['title' => 'Editar Serviço', 'user' => Auth::user(), 'service' => $service, 'categories' => $categories]);
    }

    public function update(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $data = [
            'categoria_id' => (int) input('categoria_id', '0'),
            'nome' => input('nome'),
            'descricao' => input('descricao'),
            'valor_total' => (float) input('valor_total', '0'),
            'duracao_minutos' => (int) input('duracao_minutos', '60'),
            'ativo' => input('ativo', '1') === '1' ? 1 : 0,
        ];

        $this->model()->update($id, $data);
        Logger::info('Serviço atualizado', ['id' => $id]);
        flash('success', 'Serviço atualizado com sucesso.');
        redirect('/servicos');
    }

    public function destroy(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $this->model()->delete($id);
        Logger::warning('Serviço removido', ['id' => $id]);
        flash('success', 'Serviço removido com sucesso.');
        redirect('/servicos');
    }
}
