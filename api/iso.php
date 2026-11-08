<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . '_helpers.php';

exigir_metodo_get();
validar_parametros_permitidos(['id']);
$id = validar_inteiro($_GET['id'] ?? null, 1, PHP_INT_MAX);

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
    WHERE i.id = :id AND i.ativo = 1
    LIMIT 1
';

try {
    $statement = obter_conexao_api()->prepare($sql);
    $statement->execute(['id' => $id]);
    $iso = $statement->fetch();

    if ($iso === false) {
        erro_json('ISO não encontrada', 404);
    }

    responder_json(normalizar_iso($iso));
} catch (Throwable $erro) {
    error_log('Falha ao consultar ISO: ' . $erro->getMessage());
    erro_json('Erro interno', 500);
}
