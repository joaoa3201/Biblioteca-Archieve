<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function responder_json($dados, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode(
        ['sucesso' => true, 'dados' => $dados, 'erro' => null],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function erro_json(string $mensagem, int $codigo): void
{
    http_response_code($codigo);
    echo json_encode(
        ['sucesso' => false, 'dados' => null, 'erro' => $mensagem],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function exigir_metodo_get(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        header('Allow: GET');
        erro_json('Método não permitido', 405);
    }
}

function validar_parametros_permitidos(array $permitidos): void
{
    foreach (array_keys($_GET) as $parametro) {
        if (!in_array($parametro, $permitidos, true)) {
            erro_json('Parâmetro não permitido', 400);
        }
    }
}

function validar_string($valor, int $limite, bool $obrigatoria = false): ?string
{
    if ($valor === null) {
        if ($obrigatoria) {
            erro_json('Parâmetro obrigatório ausente', 400);
        }

        return null;
    }

    if (!is_string($valor)) {
        erro_json('Parâmetro deve ser uma string', 400);
    }

    $valor = preg_replace('/[\x00-\x1F\x7F]/u', '', trim($valor));

    if ($valor === null) {
        erro_json('Parâmetro inválido', 400);
    }

    if ($valor === '') {
        if ($obrigatoria) {
            erro_json('Parâmetro obrigatório vazio', 400);
        }

        return null;
    }

    $tamanho = function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
    if ($tamanho > $limite) {
        erro_json('Parâmetro excede o limite de tamanho', 400);
    }

    return $valor;
}

function validar_inteiro($valor, int $minimo, int $maximo): int
{
    if (!is_string($valor) || !preg_match('/^\d+$/', $valor)) {
        erro_json('Parâmetro deve ser um número inteiro válido', 400);
    }

    $inteiro = filter_var($valor, FILTER_VALIDATE_INT);
    if ($inteiro === false || $inteiro < $minimo || $inteiro > $maximo) {
        erro_json('Parâmetro inteiro fora da faixa permitida', 400);
    }

    return $inteiro;
}

function validar_booleano($valor): ?int
{
    if ($valor === null) {
        return null;
    }

    if (!is_string($valor) || ($valor !== '0' && $valor !== '1')) {
        erro_json('Parâmetro booleano deve ser 0 ou 1', 400);
    }

    return (int) $valor;
}

function validar_enum($valor, array $permitidos): ?string
{
    if ($valor === null) {
        return null;
    }

    $valor = validar_string($valor, 100);
    if ($valor === null) {
        return null;
    }

    if (!in_array($valor, $permitidos, true)) {
        erro_json('Valor de enum inválido', 400);
    }

    return $valor;
}

function obter_conexao_api(): PDO
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

    return getConnection();
}

function normalizar_iso(array $iso): array
{
    return [
        'id' => (int) $iso['id'],
        'nome' => $iso['nome'],
        'familia' => $iso['familia'],
        'versao' => $iso['versao'],
        'arquitetura' => $iso['arquitetura'],
        'tamanho_mb' => (int) $iso['tamanho_mb'],
        'descricao' => $iso['descricao'],
        'perfil_recomendado' => $iso['perfil_recomendado'],
        'leve' => (bool) $iso['leve'],
        'tem_link_oficial' => (bool) $iso['tem_link_oficial'],
        'ram_min_mb' => (int) $iso['ram_min_mb'],
        'ram_rec_mb' => (int) $iso['ram_rec_mb'],
        'cpu_ghz_min' => (float) $iso['cpu_ghz_min'],
        'geracao_cpu_min' => (int) $iso['geracao_cpu_min'],
        'armazenamento_min_gb' => (int) $iso['armazenamento_min_gb'],
    ];
}
