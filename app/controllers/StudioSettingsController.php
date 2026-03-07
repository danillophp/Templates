<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\StudioSetting;
use App\Services\UploadService;
use RuntimeException;

class StudioSettingsController extends Controller
{
    private function model(): StudioSetting
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        return new StudioSetting(Database::getConnection($dbConfig));
    }

    public function edit(): void
    {
        $this->view('settings/studio', [
            'title' => 'Configurações do Studio',
            'user' => Auth::user(),
            'settings' => $this->model()->get(),
        ]);
    }

    public function save(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $current = $this->model()->get();

        $payload = [
            'nome_studio' => input('nome_studio'),
            'endereco' => input('endereco'),
            'telefone' => input('telefone'),
            'instagram' => input('instagram'),
            'facebook' => input('facebook'),
            'link_localizacao' => input('link_localizacao'),
            'horario_funcionamento' => trim((string) ($_POST['horario_funcionamento'] ?? '')),
            'logo_path' => (string) ($current['logo_path'] ?? ''),
            'ativar_convite_google' => isset($_POST['ativar_convite_google']) ? 1 : 0,
            'texto_convite_google' => trim((string) ($_POST['texto_convite_google'] ?? '')),
            'link_avaliacao_google' => input('link_avaliacao_google'),
        ];

        if ($payload['nome_studio'] === '' || $payload['telefone'] === '' || $payload['endereco'] === '') {
            flash('error', 'Nome do studio, telefone e endereço são obrigatórios.');
            $this->redirect('/configuracoes/studio');
        }

        if (isset($_FILES['logo']) && is_array($_FILES['logo']) && (int) ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploadService = new UploadService();
                $payload['logo_path'] = $uploadService->uploadStudioLogo($_FILES['logo']);
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                $this->redirect('/configuracoes/studio');
            }
        }

        $this->model()->save($payload);
        flash('success', 'Configurações atualizadas com sucesso.');
        $this->redirect('/configuracoes/studio');
    }
}
