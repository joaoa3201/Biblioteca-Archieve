<?php

declare(strict_types=1);

/**
 * POST /api/solicitar_download.php
 * Recebe o formulário que antecede o download (iso_id, nome, email, objetivo),
 * registra a solicitação em solicitacoes_download e só então libera o download.
 * Assim nenhuma URL nem arquivo fica exposto nas demais consultas da API.
 *
 * Formas de entrega, em ordem de preferência:
 *   "arquivo": o .iso está hospedado em storage/isos/ e é entregue por api/baixar.php;
 *   "direto":  link direto para o arquivo .iso (o navegador começa a baixar);
 *   "pagina":  página oficial de download do sistema.
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

    // Só aceita ISOs ativas; as URLs e o arquivo são buscados aqui, no servidor.
    $consulta = $conexao->prepare('
        SELECT url_oficial, url_download_direto, arquivo_local FROM imagens_iso WHERE id = :id AND ativo = 1
    ');
    $consulta->execute(['id' => $isoId]);
    $iso = $consulta->fetch();

    if ($iso === false) {
        erro_json('ISO não encontrada', 404);
    }

    // Escolhe a melhor forma de entrega disponível para esta ISO.
    $tipo = null;
    if (caminho_arquivo_iso($iso['arquivo_local']) !== null) {
        $tipo = 'arquivo';
    } elseif (!empty($iso['url_download_direto'])) {
        $tipo = 'direto';
    } elseif (!empty($iso['url_oficial'])) {
        $tipo = 'pagina';
    }

    if ($tipo === null) {
        erro_json('O download desta ISO ainda não está disponível', 409);
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

    if ($tipo === 'arquivo') {
        // Token aleatório guardado na sessão: vale 15 minutos e só serve para esta ISO.
        session_start();
        $token = bin2hex(random_bytes(24));
        $_SESSION['downloads'] = array_filter(
            $_SESSION['downloads'] ?? [],
            fn (array $permissao): bool => $permissao['expira'] > time()
        );
        $_SESSION['downloads'][$token] = ['iso_id' => $isoId, 'expira' => time() + 900];
        session_write_close();
        $url = 'baixar.php?token=' . $token;
    } else {
        $url = $tipo === 'direto' ? $iso['url_download_direto'] : $iso['url_oficial'];
    }

    responder_json(['tipo' => $tipo, 'url_download' => $url], 201);
} catch (Throwable $erro) {
    error_log('Falha ao registrar solicitação de download: ' . $erro->getMessage());
    erro_json('Erro interno', 500);
}
