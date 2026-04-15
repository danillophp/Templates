<?php

declare(strict_types=1);

return [
    'festival-admin/login' => ['controller' => 'Festival\\AdminController', 'action' => 'login', 'method' => 'GET|POST'],
    'festival-admin/logout' => ['controller' => 'Festival\\AdminController', 'action' => 'logout', 'method' => 'GET'],
    'festival-admin/dashboard' => ['controller' => 'Festival\\AdminController', 'action' => 'dashboard', 'method' => 'GET'],
    'festival-admin/candidates' => ['controller' => 'Festival\\AdminController', 'action' => 'candidates', 'method' => 'GET'],
    'festival-admin/votes' => ['controller' => 'Festival\\AdminController', 'action' => 'votes', 'method' => 'GET'],
    'festival-admin/fraud' => ['controller' => 'Festival\\AdminController', 'action' => 'fraud', 'method' => 'GET'],
    'festival-admin/settings' => ['controller' => 'Festival\\AdminController', 'action' => 'settings', 'method' => 'GET'],
    'api/festival-admin/candidates/save' => ['controller' => 'Festival\\AdminController', 'action' => 'saveCandidate', 'method' => 'POST'],
    'api/festival-admin/settings/save' => ['controller' => 'Festival\\AdminController', 'action' => 'saveSettings', 'method' => 'POST'],
];
