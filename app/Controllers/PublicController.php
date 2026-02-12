<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Location;

class PublicController extends BaseController
{
    public function index(Request $request): void
    {
        View::render('public/home', ['pageTitle' => 'Mapa Político Mundial']);
    }

    public function mapData(Request $request): void
    {
        $locations = (new Location())->forMap();
        Response::json(['locations' => $locations]);
    }
}
