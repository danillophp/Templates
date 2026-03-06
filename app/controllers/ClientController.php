<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Models\Client;

class ClientController extends Controller
{
    private function model(): Client
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        return new Client(Database::getConnection($dbConfig));
    }

    public function index(): void
    {
        $clients = $this->model()->all();
        $this->view('clients/index', ['title' => 'Clientes', 'user' => Auth::user(), 'clients' => $clients]);
    }

    public function create(): void
    {
        $this->view('clients/form', ['title' => 'Novo Cliente', 'user' => Auth::user(), 'client' => null]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $data = [
            'nome' => input('nome'),
            'telefone' => input('telefone'),
            'email' => input('email'),
            'data_nascimento' => input('data_nascimento'),
            'observacoes' => input('observacoes'),
        ];

        if ($data['nome'] === '' || $data['telefone'] === '') {
            flash('error', 'Nome e telefone são obrigatórios.');
            redirect('/clientes/criar');
        }

        $this->model()->create($data);
        Logger::info('Cliente cadastrado', ['nome' => $data['nome']]);
        flash('success', 'Cliente cadastrado com sucesso.');
        redirect('/clientes');
    }

    public function edit(): void
    {
        $id = (int) input('id', '0');
        $client = $this->model()->find($id);

        if (!$client) {
            flash('error', 'Cliente não encontrado.');
            redirect('/clientes');
        }

        $this->view('clients/form', ['title' => 'Editar Cliente', 'user' => Auth::user(), 'client' => $client]);
    }

    public function update(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $data = [
            'nome' => input('nome'),
            'telefone' => input('telefone'),
            'email' => input('email'),
            'data_nascimento' => input('data_nascimento'),
            'observacoes' => input('observacoes'),
        ];

        $this->model()->update($id, $data);
        Logger::info('Cliente atualizado', ['id' => $id]);
        flash('success', 'Cliente atualizado com sucesso.');
        redirect('/clientes');
    }

    public function destroy(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $id = (int) input('id', '0');
        $this->model()->delete($id);
        Logger::warning('Cliente removido', ['id' => $id]);
        flash('success', 'Cliente removido.');
        redirect('/clientes');
    }
}
