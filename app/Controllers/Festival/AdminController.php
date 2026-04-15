<?php

declare(strict_types=1);

namespace App\Controllers\Festival;

use App\Core\Auth;
use App\Core\Csrf;
use App\Helpers\ResponseHelper;
use App\Helpers\SecurityHelper;
use App\Models\Festival\AdminLogModel;
use App\Models\Festival\CandidateModel;
use App\Models\Festival\ConfigModel;
use App\Models\Festival\FraudAttemptModel;
use App\Models\Festival\VoteModel;
use App\Models\User;

final class AdminController
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['_csrf'] ?? null)) {
                $error = 'Token inválido.';
                require __DIR__ . '/../../Views/festival/admin/login.php';
                return;
            }

            $username = SecurityHelper::sanitizeText($_POST['username'] ?? '', 100);
            $password = (string) ($_POST['password'] ?? '');

            $user = (new User())->findByUsername($username);
            if (!$user || !password_verify($password, $user['password_hash']) || $user['role'] !== 'ADMIN') {
                $error = 'Credenciais inválidas.';
                require __DIR__ . '/../../Views/festival/admin/login.php';
                return;
            }

            Auth::login($user);
            (new AdminLogModel())->add((int) $user['id'], 'LOGIN', 'Login no painel festival', SecurityHelper::ip());

            header('Location: ?r=festival-admin/dashboard');
            exit;
        }

        require __DIR__ . '/../../Views/festival/admin/login.php';
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ?r=festival-admin/login');
        exit;
    }

    public function dashboard(): void
    {
        $this->guard();

        $stats = (new VoteModel())->statsOverview();
        $votesPerDay = (new VoteModel())->votesPerDay(14);
        $ranking = (new CandidateModel())->ranking(5);
        require __DIR__ . '/../../Views/festival/admin/dashboard.php';
    }

    public function candidates(): void
    {
        $this->guard();
        $candidates = (new CandidateModel())->listAdmin();
        require __DIR__ . '/../../Views/festival/admin/candidates.php';
    }

    public function votes(): void
    {
        $this->guard();
        $votes = (new VoteModel())->listPaginated(120, 0);
        require __DIR__ . '/../../Views/festival/admin/votes.php';
    }

    public function fraud(): void
    {
        $this->guard();
        $attempts = (new FraudAttemptModel())->latest(120);
        require __DIR__ . '/../../Views/festival/admin/fraud.php';
    }

    public function settings(): void
    {
        $this->guard();
        $config = (new ConfigModel())->all();
        require __DIR__ . '/../../Views/festival/admin/settings.php';
    }

    public function saveCandidate(): void
    {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['_csrf'] ?? null)) {
            ResponseHelper::json(['ok' => false, 'message' => 'Requisição inválida.'], 400);
            return;
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $data = [
            'numero' => (int) ($_POST['numero'] ?? 0),
            'nome' => SecurityHelper::sanitizeText($_POST['nome'] ?? '', 120),
            'slug' => SecurityHelper::sanitizeText($_POST['slug'] ?? '', 140),
            'idade' => (int) ($_POST['idade'] ?? 0),
            'cidade' => SecurityHelper::sanitizeText($_POST['cidade'] ?? '', 120),
            'descricao' => SecurityHelper::sanitizeText($_POST['descricao'] ?? '', 500),
            'foto' => SecurityHelper::sanitizeText($_POST['foto'] ?? '', 255),
            'status' => in_array($_POST['status'] ?? '', ['ATIVA', 'INATIVA'], true) ? $_POST['status'] : 'INATIVA',
            'exibir_publicamente' => isset($_POST['exibir_publicamente']) ? 1 : 0,
        ];

        $model = new CandidateModel();
        if ($id) {
            $model->update($id, $data);
            ResponseHelper::json(['ok' => true, 'message' => 'Candidata atualizada.']);
            return;
        }

        $newId = $model->create($data);
        ResponseHelper::json(['ok' => true, 'message' => 'Candidata criada.', 'id' => $newId]);
    }

    public function saveSettings(): void
    {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['_csrf'] ?? null)) {
            ResponseHelper::json(['ok' => false, 'message' => 'Requisição inválida.'], 400);
            return;
        }

        $model = new ConfigModel();
        foreach ($_POST['configs'] ?? [] as $key => $value) {
            $model->set(SecurityHelper::sanitizeText((string) $key, 100), SecurityHelper::sanitizeText((string) $value, 500));
        }

        ResponseHelper::json(['ok' => true, 'message' => 'Configurações salvas com sucesso.']);
    }

    private function guard(): void
    {
        if (!Auth::check() || !Auth::is('ADMIN')) {
            header('Location: ?r=festival-admin/login');
            exit;
        }
    }
}
