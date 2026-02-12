<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Location;
use App\Models\Politician;

class PoliticianController extends BaseController
{
    public function index(Request $request): void
    {
        $this->requireAuth();
        $model = new Politician();

        View::render('admin/politicians/index', [
            'pageTitle' => 'Gerenciar Políticos',
            'politicians' => $model->allWithLocation(),
            'locations' => (new Location())->all(),
            'editing' => null,
            'csrfField' => $this->csrfField(),
            'flash' => $_SESSION['flash_success'] ?? null,
        ], 'layouts/admin');

        unset($_SESSION['flash_success']);
    }

    public function edit(Request $request): void
    {
        $this->requireAuth();
        $model = new Politician();

        View::render('admin/politicians/index', [
            'pageTitle' => 'Gerenciar Políticos',
            'politicians' => $model->allWithLocation(),
            'locations' => (new Location())->all(),
            'editing' => $model->find((int) $request->input('id')),
            'csrfField' => $this->csrfField(),
            'flash' => $_SESSION['flash_success'] ?? null,
        ], 'layouts/admin');

        unset($_SESSION['flash_success']);
    }

    public function create(Request $request): void
    {
        $this->requireAuth();
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));

        $data = $this->sanitize($request);
        $data['photo_path'] = $this->uploadPhoto($_FILES['photo'] ?? null);
        (new Politician())->create($data);

        $_SESSION['flash_success'] = 'Político cadastrado com sucesso.';
        Response::redirect('/admin/politicians');
    }

    public function update(Request $request): void
    {
        $this->requireAuth();
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));

        $id = (int) $request->input('id');
        $model = new Politician();
        $existing = $model->find($id);
        if (!$existing) {
            Response::redirect('/admin/politicians');
        }

        $data = $this->sanitize($request);
        $uploaded = $this->uploadPhoto($_FILES['photo'] ?? null);
        $data['photo_path'] = $uploaded ?: ($existing['photo_path'] ?? null);

        $model->update($id, $data);

        $_SESSION['flash_success'] = 'Político atualizado com sucesso.';
        Response::redirect('/admin/politicians');
    }

    public function delete(Request $request): void
    {
        $this->requireAuth();
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));

        (new Politician())->delete((int) $request->input('id'));
        $_SESSION['flash_success'] = 'Político removido com sucesso.';
        Response::redirect('/admin/politicians');
    }

    private function sanitize(Request $request): array
    {
        return [
            'location_id' => (int) $request->input('location_id'),
            'full_name' => trim((string) $request->input('full_name')),
            'position' => trim((string) $request->input('position')),
            'party' => trim((string) $request->input('party')),
            'age' => (int) $request->input('age'),
            'biography' => trim((string) $request->input('biography')),
            'career_history' => trim((string) $request->input('career_history')),
            'municipality_history' => trim((string) $request->input('municipality_history')),
            'phone' => trim((string) $request->input('phone')),
            'email' => trim((string) $request->input('email')),
            'advisors' => trim((string) $request->input('advisors')),
        ];
    }

    private function uploadPhoto(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            return null;
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return null;
        }

        $filename = 'politico_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        $destinationDir = __DIR__ . '/../../public/assets/uploads';
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $destination = $destinationDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return null;
        }

        return '/assets/uploads/' . $filename;
    }
}
