<?php

/**
 * Funções compartilhadas por todos os endpoints da API.
 *
 * Padroniza o formato das respostas (JSON com sucesso/dados/erro) e concentra
 * a validação das entradas, para que nenhum endpoint confie em dados do usuário.
 */

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

/** Rejeita parâmetros desconhecidos na URL (lista branca). */
function validar_parametros_permitidos(array $permitidos): void
{
    foreach (array_keys($_GET) as $parametro) {
        if (!in_array($parametro, $permitidos, true)) {
            erro_json('Parâmetro não permitido', 400);
        }
    }
}

/** Valida um texto: remove caracteres de controle, aplica limite de tamanho e obrigatoriedade. */
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

/** Abre a conexão com o banco; erros de conexão são tratados em cada endpoint. */
function obter_conexao_api(): PDO
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

    return getConnection();
}

/** Converte os campos do banco para os tipos corretos (número, booleano) antes de enviar o JSON. */
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
        'localizacao' => $iso['localizacao'],           // onde o arquivo está (texto livre); pode ser nulo
        'checksum_sha256' => $iso['checksum_sha256'],   // para o usuário conferir o arquivo baixado; pode ser nulo
        'ram_min_mb' => (int) $iso['ram_min_mb'],
        'ram_rec_mb' => (int) $iso['ram_rec_mb'],
        'cpu_ghz_min' => (float) $iso['cpu_ghz_min'],
        'geracao_cpu_min' => (int) $iso['geracao_cpu_min'],
        'armazenamento_min_gb' => (int) $iso['armazenamento_min_gb'],
    ];
}

/**
 * SQL base que une ISO, sistema operacional e requisitos.
 * Os endpoints acrescentam apenas o WHERE/ORDER BY, evitando repetir o SELECT.
 * Obs.: a URL oficial não é exposta; só informamos se ela existe (tem_link_oficial),
 * pois o link só deve ser liberado depois do formulário de download.
 */
function consulta_base_iso(): string
{
    return '
    SELECT
        i.id,
        so.nome,
        so.familia,
        i.versao,
        i.arquitetura,
        i.tamanho_mb,
        i.localizacao_arquivo AS localizacao,
        i.checksum_sha256,
        so.descricao,
        so.perfil_recomendado,
        so.leve,
        CASE WHEN i.url_oficial IS NOT NULL AND i.url_oficial <> "" THEN 1 ELSE 0 END AS tem_link_oficial,
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
}

/** Valida um número decimal positivo recebido por texto (ex.: "2.4"). */
function validar_decimal($valor, float $minimo, float $maximo): float
{
    if (!is_string($valor) || !preg_match('/^\d+(\.\d+)?$/', $valor)) {
        erro_json('Parâmetro deve ser um número válido', 400);
    }

    $decimal = (float) $valor;
    if ($decimal < $minimo || $decimal > $maximo) {
        erro_json('Parâmetro numérico fora da faixa permitida', 400);
    }

    return $decimal;
}

/** Garante que a requisição use o método POST (usado nos endpoints que gravam dados). */
function exigir_metodo_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        erro_json('Método não permitido', 405);
    }
}

/** Lê o corpo da requisição como JSON e devolve um array (ou erro 400). */
function ler_corpo_json(): array
{
    $corpo = json_decode(file_get_contents('php://input') ?: '', true);

    if (!is_array($corpo)) {
        erro_json('Corpo da requisição inválido', 400);
    }

    return $corpo;
}
