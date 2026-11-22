<?php

declare(strict_types=1);

/**
 * GET /api/baixar.php?token=...
 * Entrega o arquivo .iso hospedado no servidor (pasta storage/isos/).
 *
 * O token só é criado por api/solicitar_download.php depois que o formulário é
 * preenchido, vale por 15 minutos e fica guardado na sessão do visitante; sem ele,
 * não há como baixar o arquivo. Aceita o cabeçalho Range, o que permite pausar e
 * retomar o download de arquivos grandes.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '_helpers.php';

exigir_metodo_get();
validar_parametros_permitidos(['token']);
$token = validar_string($_GET['token'] ?? null, 64, true);

session_start();
$permissao = $_SESSION['downloads'][$token] ?? null;
session_write_close(); // libera a sessão: o download pode demorar e não deve travar outras requisições

if (!is_array($permissao) || $permissao['expira'] < time()) {
    erro_json('Link de download expirado. Preencha o formulário novamente.', 403);
}

try {
    $consulta = obter_conexao_api()->prepare('SELECT arquivo_local FROM imagens_iso WHERE id = :id AND ativo = 1');
    $consulta->execute(['id' => $permissao['iso_id']]);
    $nomeArquivo = $consulta->fetchColumn();
} catch (Throwable $erro) {
    error_log('Falha ao localizar arquivo da ISO: ' . $erro->getMessage());
    erro_json('Erro interno', 500);
}

$caminho = is_string($nomeArquivo) ? caminho_arquivo_iso($nomeArquivo) : null;
if ($caminho === null) {
    erro_json('Arquivo não encontrado no servidor', 404);
}

$tamanho = filesize($caminho);
$inicio = 0;
$fim = $tamanho - 1;
$codigo = 200;

// Suporte a download parcial/retomável: "Range: bytes=INICIO-FIM".
if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'], $m) && ($m[1] !== '' || $m[2] !== '')) {
    if ($m[1] === '') {                       // "-N": os últimos N bytes
        $inicio = max(0, $tamanho - (int) $m[2]);
    } else {
        $inicio = (int) $m[1];
        $fim = $m[2] !== '' ? min((int) $m[2], $tamanho - 1) : $tamanho - 1;
    }

    if ($inicio > $fim || $inicio >= $tamanho) {
        header('Content-Range: bytes */' . $tamanho);
        erro_json('Faixa solicitada inválida', 416);
    }
    $codigo = 206;
}

// Troca o tipo JSON (definido em _helpers.php) pelo de arquivo para download.
http_response_code($codigo);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($caminho) . '"');
header('Accept-Ranges: bytes');
header('Content-Length: ' . ($fim - $inicio + 1));
if ($codigo === 206) {
    header("Content-Range: bytes {$inicio}-{$fim}/{$tamanho}");
}

set_time_limit(0);
while (ob_get_level() > 0) {
    ob_end_clean();
}

$arquivo = fopen($caminho, 'rb');
fseek($arquivo, $inicio);
$restante = $fim - $inicio + 1;

// Lê e envia em blocos de 1 MB para não carregar o arquivo inteiro na memória.
while ($restante > 0 && !feof($arquivo) && !connection_aborted()) {
    $bloco = fread($arquivo, (int) min(1048576, $restante));
    if ($bloco === false) {
        break;
    }
    echo $bloco;
    flush();
    $restante -= strlen($bloco);
}

fclose($arquivo);
