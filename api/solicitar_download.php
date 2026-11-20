<?php

declare(strict_types=1);

/**
 * POST /api/solicitar_download.php
 * Recebe o formulário que antecede o download (iso_id, nome, email, objetivo),
 * registra a solicitação em solicitacoes_download e só então devolve o link
 * oficial da ISO. Assim a URL nunca fica exposta nas demais consultas da API.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '_helpers.php';

exigir_metodo_post();
$corpo = ler_corpo_json();

$isoId = $corpo['iso_id'] ?? null;
if (!is_int($isoId) || $isoId < 1) {
    erro_json('ISO inválida', 400);
}

$nome = validar_string($corpo['nome'] ?? null, 150, true);
$email = validar_string($corpo['email'] ?? null, 254, true);
$objetivo = validar_string($corpo['objetivo'] ?? null, 1000, true);

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    erro_json('Informe um e-mail válido', 400);
}

try {
    $conexao = obter_conexao_api();

    // Só aceita ISOs ativas; a URL é buscada aqui, no servidor.
    $consulta = $conexao->prepare('SELECT url_oficial FROM imagens_iso WHERE id = :id AND ativo = 1');
    $consulta->execute(['id' => $isoId]);
    $iso = $consulta->fetch();

    if ($iso === false) {
        erro_json('ISO não encontrada', 404);
    }

    if ($iso['url_oficial'] === null || $iso['url_oficial'] === '') {
        erro_json('O link oficial desta ISO ainda não está disponível', 409);
    }

    $insercao = $conexao->prepare('
        INSERT INTO solicitacoes_download (iso_id, nome, email, objetivo, ip)
        VALUES (:iso_id, :nome, :email, :objetivo, :ip)
    ');
    $insercao->execute([
        'iso_id' => $isoId,
        'nome' => $nome,
        'email' => $email,
        'objetivo' => $objetivo,
        'ip' => substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45),
    ]);

    responder_json(['url_download' => $iso['url_oficial']], 201);
} catch (Throwable $erro) {
    error_log('Falha ao registrar solicitação de download: ' . $erro->getMessage());
    erro_json('Erro interno', 500);
}
