<?php

declare(strict_types=1);

/**
 * GET /api/isos.php
 * Lista as ISOs ativas, com filtros opcionais: busca (nome), familia, versao,
 * arquitetura e leve. Todos os filtros entram na consulta como parâmetros
 * (prepared statements), nunca concatenados, para evitar SQL injection.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '_helpers.php';

exigir_metodo_get();
validar_parametros_permitidos(['busca', 'familia', 'versao', 'arquitetura', 'leve']);

$busca = validar_string($_GET['busca'] ?? null, 150);
$familia = validar_enum($_GET['familia'] ?? null, ['Linux', 'Windows', 'macOS', 'Outro']);
$versao = validar_string($_GET['versao'] ?? null, 100);
$arquitetura = validar_enum($_GET['arquitetura'] ?? null, ['x86', 'x64', 'arm64']);
$leve = validar_booleano($_GET['leve'] ?? null);

$sql = consulta_base_iso();
$parametros = [];

if ($busca !== null) {
    // Escapa os curingas do LIKE (% e _) para que a busca seja literal.
    $buscaEscapada = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busca);
    $sql .= " AND so.nome LIKE :busca ESCAPE '!'";
    $parametros['busca'] = '%' . $buscaEscapada . '%';
}

if ($familia !== null) {
    $sql .= ' AND so.familia = :familia';
    $parametros['familia'] = $familia;
}

if ($versao !== null) {
    $sql .= ' AND i.versao = :versao';
    $parametros['versao'] = $versao;
}

if ($arquitetura !== null) {
    $sql .= ' AND i.arquitetura = :arquitetura';
    $parametros['arquitetura'] = $arquitetura;
}

if ($leve !== null) {
    $sql .= ' AND so.leve = :leve';
    $parametros['leve'] = $leve;
}

$sql .= ' ORDER BY so.nome ASC, i.versao ASC';

try {
    $statement = obter_conexao_api()->prepare($sql);
    $statement->execute($parametros);
    $isos = array_map('normalizar_iso', $statement->fetchAll());

    responder_json($isos);
} catch (Throwable $erro) {
    error_log('Falha ao listar ISOs: ' . $erro->getMessage());
    erro_json('Erro interno', 500);
}
