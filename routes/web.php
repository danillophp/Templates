<?php

declare(strict_types=1);

return [
    'festival/home' => ['controller' => 'Festival\\PublicController', 'action' => 'home', 'method' => 'GET'],
    'api/festival/vote' => ['controller' => 'Festival\\VoteController', 'action' => 'store', 'method' => 'POST'],
    'api/festival/ranking' => ['controller' => 'Festival\\VoteController', 'action' => 'ranking', 'method' => 'GET'],
];
