<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . '_helpers.php';

exigir_metodo_get();
validar_parametros_permitidos([]);

try {
    $conexao = obter_conexao_api();
    $statementFamilias = $conexao->prepare('
        SELECT DISTINCT so.familia
        FROM sistemas_operacionais so
        INNER JOIN imagens_iso i ON i.sistema_id = so.id
        WHERE i.ativo = 1
        ORDER BY so.familia ASC
    ');
    $statementFamilias->execute();

    $statementVersoes = $conexao->prepare('
        SELECT DISTINCT i.versao
        FROM imagens_iso i
        WHERE i.ativo = 1
        ORDER BY i.versao ASC
    ');
    $statementVersoes->execute();

    responder_json([
        'familias' => array_column($statementFamilias->fetchAll(), 'familia'),
        'versoes' => array_column($statementVersoes->fetchAll(), 'versao'),
    ]);
} catch (Throwable $erro) {
    error_log('Falha ao consultar filtros de ISO: ' . $erro->getMessage());
    erro_json('Erro interno', 500);
}
