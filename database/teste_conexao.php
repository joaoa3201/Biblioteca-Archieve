<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este script deve ser executado pela linha de comando.\n");
}

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$sql = '
    SELECT so.familia, COUNT(iso.id) AS total
    FROM sistemas_operacionais so
    LEFT JOIN imagens_iso iso ON iso.sistema_id = so.id
    GROUP BY so.familia
';
$statement = getConnection()->prepare($sql);
$statement->execute();
$totais = [];

foreach ($statement->fetchAll() as $row) {
    $totais[$row['familia']] = (int) $row['total'];
}

foreach (['Linux', 'Windows', 'macOS', 'Outro'] as $familia) {
    printf("%s: %d ISO(s)\n", $familia, $totais[$familia] ?? 0);
}
