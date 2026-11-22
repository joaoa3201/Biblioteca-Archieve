<?php

declare(strict_types=1);

/**
 * Exclui uma ISO. Se ela já tem solicitações de download registradas, não é
 * apagada (para não perder o histórico): apenas é desativada.
 */

require_once __DIR__ . '/_comum.php';
exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('index.php');
}
validar_csrf();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    redirecionar('index.php');
}

$conexao = getConnection();
$conexao->beginTransaction();

try {
    $busca = $conexao->prepare('SELECT sistema_id FROM imagens_iso WHERE id = :id');
    $busca->execute(['id' => $id]);
    $iso = $busca->fetch();

    if ($iso === false) {
        $conexao->rollBack();
        redirecionar('index.php');
    }

    $contagem = $conexao->prepare('SELECT COUNT(*) FROM solicitacoes_download WHERE iso_id = :id');
    $contagem->execute(['id' => $id]);

    if ((int) $contagem->fetchColumn() > 0) {
        $conexao->prepare('UPDATE imagens_iso SET ativo = 0 WHERE id = :id')->execute(['id' => $id]);
        $conexao->commit();
        redirecionar('index.php?aviso=desativada');
    }

    $conexao->prepare('DELETE FROM requisitos WHERE iso_id = :id')->execute(['id' => $id]);
    $conexao->prepare('DELETE FROM imagens_iso WHERE id = :id')->execute(['id' => $id]);

    // Remove o sistema operacional também, se ficou sem nenhuma ISO.
    $conexao->prepare('
        DELETE FROM sistemas_operacionais
        WHERE id = :sistema AND NOT EXISTS (SELECT 1 FROM imagens_iso WHERE sistema_id = :sistema2)
    ')->execute(['sistema' => $iso['sistema_id'], 'sistema2' => $iso['sistema_id']]);

    $conexao->commit();
} catch (Throwable $erro) {
    $conexao->rollBack();
    error_log('Falha ao excluir ISO: ' . $erro->getMessage());
    http_response_code(500);
    exit('Erro ao excluir a ISO.');
}

redirecionar('index.php?aviso=excluida');
