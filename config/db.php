<?php

declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db_connect(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $connection = new mysqli('localhost', 'catatreco', 'php@3903', 'santo821_treco');
    $connection->set_charset('utf8mb4');

    return $connection;
}
