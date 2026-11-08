<?php

declare(strict_types=1);

$envPath = __DIR__ . DIRECTORY_SEPARATOR . '.env';

if (!is_file($envPath) || !is_readable($envPath)) {
    throw new RuntimeException(
        'Arquivo config/.env não encontrado ou sem permissão de leitura. '
        . 'Crie-o a partir de config/.env.example.'
    );
}

$config = [];
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($lines === false) {
    throw new RuntimeException('Não foi possível ler config/.env.');
}

foreach ($lines as $line) {
    $line = trim($line);

    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    $separator = strpos($line, '=');

    if ($separator === false) {
        continue;
    }

    $key = trim(substr($line, 0, $separator));
    $value = trim(substr($line, $separator + 1));

    if ($key === '') {
        continue;
    }

    if (
        strlen($value) >= 2
        && (($value[0] === '"' && $value[strlen($value) - 1] === '"')
            || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
    ) {
        $value = substr($value, 1, -1);
    }

    $config[$key] = $value;
}

foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $requiredKey) {
    if (!array_key_exists($requiredKey, $config)) {
        throw new RuntimeException("A variável {$requiredKey} não foi definida em config/.env.");
    }
}

return $config;
