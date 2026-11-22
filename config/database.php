<?php

/** Conexão com o MySQL via PDO. */

declare(strict_types=1);

/**
 * Devolve a conexão PDO, criada uma única vez por requisição (padrão singleton).
 * ERRMODE_EXCEPTION faz erros de SQL virarem exceções, e EMULATE_PREPARES = false
 * usa prepared statements reais do MySQL.
 */
function getConnection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $config = require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $config['DB_HOST'],
        $config['DB_NAME']
    );

    $connection = new PDO(
        $dsn,
        $config['DB_USER'],
        $config['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    return $connection;
}
