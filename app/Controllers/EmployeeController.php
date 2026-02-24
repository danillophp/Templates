<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Whatsapp;
use App\Models\PickupRequest;

class EmployeeController extends BaseController
{
    public function panel(Request $request): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $requests = (new PickupRequest())->listAssigned((int) $user['id']);

        View::render('admin/employee/index', [
            'pageTitle' => 'Painel do Funcionário',
            'user' => $user,
            'requests' => $requests,
            'csrfField' => $this->csrfField(),
        ], 'layouts/admin');
    }

    public function start(Request $request): void
    {
        $this->requireAuth();
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));
        $id = (int) $request->input('id');
        (new PickupRequest())->markStarted($id);
        (new PickupRequest())->addStatusHistory($id, 'EM_ANDAMENTO', (int) Auth::user()['id'], 'Coleta iniciada');
        $this->audit('funcionario_iniciou_coleta', ['request_id' => $id]);
        Response::redirect('/funcionario');
    }

    public function finish(Request $request): void
    {
        $this->requireAuth();
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));
        $id = (int) $request->input('id');
        $model = new PickupRequest();
        $row = $model->find($id);
        if ($row) {
            $model->markFinished($id);
            $model->addStatusHistory($id, 'FINALIZADA', (int) Auth::user()['id'], 'Coleta finalizada');
            $msg = "Olá {$row['citizen_name']}, sua coleta Cata Treco foi finalizada. Obrigado!";
            $whats = Whatsapp::notify($row['whatsapp'], $msg);
            $this->audit('funcionario_finalizou_coleta', ['request_id' => $id, 'whatsapp' => $whats]);
        }
        Response::redirect('/funcionario');
    }
}
