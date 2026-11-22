<?php

declare(strict_types=1);

/**
 * Calcula o SHA-256 dos arquivos .iso hospedados em storage/isos/ e grava no banco
 * (coluna checksum_sha256), para o usuário conferir a integridade depois de baixar.
 * Uso, no terminal:  php database/calcular_checksum.php
 */

if (PHP_SAPI !== 'cli') {
    exit('Execute este script pelo terminal.');
}

require_once __DIR__ . '/../config/database.php';

$conexao = getConnection();
$isos = $conexao->query('SELECT id, arquivo_local FROM imagens_iso WHERE arquivo_local IS NOT NULL')->fetchAll();
$atualiza = $conexao->prepare('UPDATE imagens_iso SET checksum_sha256 = :hash WHERE id = :id');

foreach ($isos as $iso) {
    $caminho = __DIR__ . '/../storage/isos/' . basename($iso['arquivo_local']);

    if (!is_file($caminho)) {
        echo "Arquivo não encontrado: {$iso['arquivo_local']}\n";
        continue;
    }

    $hash = hash_file('sha256', $caminho);
    $atualiza->execute(['hash' => $hash, 'id' => $iso['id']]);
    echo "{$iso['arquivo_local']}: {$hash}\n";
}
