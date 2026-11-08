<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . '_helpers.php';

exigir_metodo_get();
validar_parametros_permitidos(['busca', 'familia', 'versao', 'arquitetura', 'leve']);

$busca = validar_string($_GET['busca'] ?? null, 150);
$familia = validar_enum($_GET['familia'] ?? null, ['Linux', 'Windows', 'macOS', 'Outro']);
$versao = validar_string($_GET['versao'] ?? null, 100);
$arquitetura = validar_enum($_GET['arquitetura'] ?? null, ['x86', 'x64', 'arm64']);
$leve = validar_booleano($_GET['leve'] ?? null);

$sql = '
    SELECT
        i.id,
        so.nome,
        so.familia,
        i.versao,
        i.arquitetura,
        i.tamanho_mb,
        so.descricao,
        so.perfil_recomendado,
        so.leve,
        CASE WHEN i.url_oficial IS NOT NULL AND i.url_oficial <> \'\' THEN 1 ELSE 0 END AS tem_link_oficial,
        r.ram_min_mb,
        r.ram_rec_mb,
        r.cpu_ghz_min,
        r.geracao_cpu_min,
        r.armazenamento_min_gb
    FROM imagens_iso i
    INNER JOIN sistemas_operacionais so ON so.id = i.sistema_id
    INNER JOIN requisitos r ON r.iso_id = i.id
    WHERE i.ativo = 1
';
$parametros = [];

if ($busca !== null) {
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
