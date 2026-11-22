<?php

declare(strict_types=1);

/** GET /api/iso.php?id=N — devolve os dados e requisitos de uma ISO. */

require_once __DIR__ . DIRECTORY_SEPARATOR . '_helpers.php';

exigir_metodo_get();
validar_parametros_permitidos(['id']);
$id = validar_inteiro($_GET['id'] ?? null, 1, PHP_INT_MAX);

$sql = consulta_base_iso() . ' AND i.id = :id LIMIT 1';

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
