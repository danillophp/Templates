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
        $this->view('services/index', [
            'title' => 'Serviços',
            'user' => Auth::user(),
            'services' => $services,
        ]);
    }

    public function create(): void
    {
        $categories = $this->categoryModel()->all();
        $this->view('services/form', [
            'title' => 'Novo Serviço',
            'user' => Auth::user(),
            'service' => null,
            'categories' => $categories,
        ]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $data = $this->validatePayload();
        if ($data === null) {
            redirect('/servicos/criar');
        }

        if ($this->model()->existsByName($data['nome'], $data['categoria_id'])) {
            flash('error', 'Já existe um serviço com esse nome nesta categoria.');
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
        $this->view('services/form', [
            'title' => 'Editar Serviço',
            'user' => Auth::user(),
            'service' => $service,
            'categories' => $categories,
        ]);
    }

    public function update(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $service = $this->model()->find($id);

        if (!$service) {
            flash('error', 'Serviço não encontrado.');
            redirect('/servicos');
        }

        $data = $this->validatePayload();
        if ($data === null) {
            redirect('/servicos/editar?id=' . $id);
        }

        if ($this->model()->existsByName($data['nome'], $data['categoria_id'], $id)) {
            flash('error', 'Já existe um serviço com esse nome nesta categoria.');
            redirect('/servicos/editar?id=' . $id);
        }

        $this->model()->update($id, $data);
        Logger::info('Serviço atualizado', ['id' => $id]);
        flash('success', 'Serviço atualizado com sucesso.');
        redirect('/servicos');
    }

    public function toggleStatus(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $service = $this->model()->find($id);

        if (!$service) {
            flash('error', 'Serviço não encontrado.');
            redirect('/servicos');
        }

        $this->model()->toggleStatus($id);
        Logger::info('Serviço alterou status', ['id' => $id]);
        flash('success', 'Status do serviço atualizado.');
        redirect('/servicos');
    }

    public function destroy(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $service = $this->model()->find($id);

        if (!$service) {
            flash('error', 'Serviço não encontrado.');
            redirect('/servicos');
        }

        $this->model()->softDelete($id);
        Logger::warning('Serviço excluído logicamente', ['id' => $id]);
        flash('success', 'Serviço excluído logicamente (inativado).');
        redirect('/servicos');
    }

    private function validatePayload(): ?array
    {
        $data = [
            'categoria_id' => (int) input('categoria_id', '0'),
            'nome' => input('nome'),
            'descricao' => input('descricao'),
            'duracao_minutos' => (int) input('duracao_minutos', '60'),
            'valor' => (float) input('valor', '0'),
            'percentual_entrada' => (float) input('percentual_entrada', '20'),
            'ativo' => input('ativo', '1') === '1' ? 1 : 0,
        ];

        if ($data['categoria_id'] <= 0) {
            flash('error', 'Selecione uma categoria válida.');
            return null;
        }

        if (mb_strlen($data['nome']) < 3) {
            flash('error', 'Nome do serviço deve ter ao menos 3 caracteres.');
            return null;
        }

        if ($data['duracao_minutos'] < 15) {
            flash('error', 'Duração mínima permitida é 15 minutos.');
            return null;
        }

        if ($data['valor'] <= 0) {
            flash('error', 'Valor do serviço deve ser maior que zero.');
            return null;
        }

        if ($data['percentual_entrada'] <= 0) {
            $data['percentual_entrada'] = 20.00;
        }

        return $data;
    }
}
