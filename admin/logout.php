<?php

declare(strict_types=1);

require_once __DIR__ . '/_comum.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $_SESSION = [];
    session_destroy();
}

redirecionar('login.php');
