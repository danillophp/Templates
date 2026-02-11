<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Location;

class LocationController extends BaseController
{
    public function index(Request $request): void
    {
        $this->requireAuth();
        $model = new Location();

        View::render('admin/locations/index', [
            'pageTitle' => 'Gerenciar Localizações',
            'locations' => $model->all(),
            'editing' => null,
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
        (new Location())->create($data);

        $_SESSION['flash_success'] = 'Localização cadastrada com sucesso.';
        Response::redirect('/admin/locations');
    }

    public function edit(Request $request): void
    {
        $this->requireAuth();
        $model = new Location();
        $id = (int) $request->input('id');

        View::render('admin/locations/index', [
            'pageTitle' => 'Gerenciar Localizações',
            'locations' => $model->all(),
            'editing' => $model->find($id),
            'csrfField' => $this->csrfField(),
            'flash' => $_SESSION['flash_success'] ?? null,
        ], 'layouts/admin');

        unset($_SESSION['flash_success']);
    }

    public function update(Request $request): void
    {
        $this->requireAuth();
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));

        $id = (int) $request->input('id');
        $data = $this->sanitize($request);
        (new Location())->update($id, $data);

        $_SESSION['flash_success'] = 'Localização atualizada com sucesso.';
        Response::redirect('/admin/locations');
    }

    public function delete(Request $request): void
    {
        $this->requireAuth();
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));

        (new Location())->delete((int) $request->input('id'));
        $_SESSION['flash_success'] = 'Localização removida com sucesso.';
        Response::redirect('/admin/locations');
    }

    private function sanitize(Request $request): array
    {
        return [
            'name' => trim((string) $request->input('name')),
            'address' => trim((string) $request->input('address')),
            'postal_code' => trim((string) $request->input('postal_code')),
            'latitude' => (float) $request->input('latitude'),
            'longitude' => (float) $request->input('longitude'),
            'city_info' => trim((string) $request->input('city_info')),
            'region_info' => trim((string) $request->input('region_info')),
        ];
    }
}
