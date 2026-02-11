<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Models\Location;
use App\Models\Politician;

class AdminController extends BaseController
{
    public function dashboard(Request $request): void
    {
        $this->requireAuth();

        View::render('admin/dashboard/index', [
            'pageTitle' => 'Painel Administrativo',
            'user' => Auth::user(),
            'locationsCount' => count((new Location())->all()),
            'politiciansCount' => count((new Politician())->allWithLocation()),
            'csrfField' => $this->csrfField(),
        ], 'layouts/admin');
    }
}
