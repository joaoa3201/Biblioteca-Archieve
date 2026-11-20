<?php

declare(strict_types=1);

/**
 * POST /api/chat.php
 * Chatbot consultivo: recebe o histórico da conversa, envia à API da Anthropic
 * junto com o catálogo de ISOs do banco e devolve a resposta.
 *
 * Corpo: { "mensagens": [ { "papel": "user" | "assistant", "texto": "..." } ] }
 * Resposta: { "resposta": "texto", "isos": [ { "id": 1, "nome": "Ubuntu" } ] }
 *
 * A chave de API fica só em config/.env (ANTHROPIC_API_KEY) e é lida aqui, no PHP;
 * o navegador nunca a vê. Como o modelo só enxerga as ISOs do catálogo enviado no
 * prompt, ele recomenda apenas sistemas que realmente existem na biblioteca.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '_helpers.php';

const MAX_MENSAGENS = 20;          // tamanho máximo do histórico aceito
const MAX_CARACTERES = 1000;       // tamanho máximo de cada mensagem
const LIMITE_POR_HORA = 30;        // proteção simples contra abuso (cobra-se por uso da API)

exigir_metodo_post();
$config = require __DIR__ . '/../config/config.php';

$chave = $config['ANTHROPIC_API_KEY'] ?? '';
if ($chave === '') {
    erro_json('O assistente ainda não foi configurado pelo administrador.', 503);
}

// ---- Limite de uso por visitante (sessão) ----
session_start();
$agora = time();
$janela = array_filter($_SESSION['chat_acessos'] ?? [], fn (int $t): bool => $t > $agora - 3600);
if (count($janela) >= LIMITE_POR_HORA) {
    erro_json('Muitas perguntas em pouco tempo. Tente novamente mais tarde.', 429);
}
$janela[] = $agora;
$_SESSION['chat_acessos'] = array_values($janela);
session_write_close();

// ---- Validação do histórico recebido ----
$corpo = ler_corpo_json();
$recebidas = $corpo['mensagens'] ?? null;
if (!is_array($recebidas) || $recebidas === [] || count($recebidas) > MAX_MENSAGENS) {
    erro_json('Histórico de mensagens inválido', 400);
}

$mensagens = [];
foreach ($recebidas as $mensagem) {
    $papel = is_array($mensagem) ? ($mensagem['papel'] ?? null) : null;
    $texto = is_array($mensagem) ? validar_string($mensagem['texto'] ?? null, MAX_CARACTERES, true) : null;
    if (!in_array($papel, ['user', 'assistant'], true) || $texto === null) {
        erro_json('Mensagem inválida', 400);
    }
    $mensagens[] = ['role' => $papel, 'content' => $texto];
}

// A API exige que a conversa comece e termine com o usuário.
if ($mensagens[0]['role'] !== 'user' || $mensagens[count($mensagens) - 1]['role'] !== 'user') {
    erro_json('A conversa deve começar e terminar com uma mensagem do usuário', 400);
}

try {
    $conexao = obter_conexao_api();
    $catalogo = $conexao->query(consulta_base_iso() . ' ORDER BY so.nome ASC')->fetchAll();
} catch (Throwable $erro) {
    error_log('Falha ao carregar catálogo para o chat: ' . $erro->getMessage());
    erro_json('Erro interno', 500);
}

// ---- Prompt do sistema: papel do assistente + catálogo do banco ----
$linhas = [];
$idsValidos = [];
foreach ($catalogo as $linha) {
    $iso = normalizar_iso($linha);
    $idsValidos[$iso['id']] = $iso['nome'];
    $linhas[] = sprintf(
        '[id %d] %s (%s, %s, %d MB) | RAM mínima %d MB, recomendada %d MB | processador a partir de %.2f GHz e %dª geração | disco %d GB | %s',
        $iso['id'], $iso['nome'], $iso['versao'], $iso['arquitetura'], $iso['tamanho_mb'],
        $iso['ram_min_mb'], $iso['ram_rec_mb'], $iso['cpu_ghz_min'], $iso['geracao_cpu_min'],
        $iso['armazenamento_min_gb'], $iso['leve'] ? 'sistema leve' : 'sistema comum'
    );
}

$sistema = "Você é o assistente da Biblioteca Archive, um site que ajuda pessoas leigas a reaproveitar computadores antigos instalando um sistema operacional compatível, reduzindo o lixo eletrônico.\n"
    . "Regras:\n"
    . "- Responda sempre em português do Brasil, com frases curtas e sem jargão; explique termos técnicos quando usá-los.\n"
    . "- Seu papel é consultivo: pergunte o que faltar sobre o computador (memória RAM, processador e sua geração/clock, espaço de armazenamento) e para que ele será usado. Faça uma ou duas perguntas por vez.\n"
    . "- Recomende somente ISOs da lista abaixo, comparando o hardware do usuário com os requisitos. Nunca invente sistemas, versões ou requisitos.\n"
    . "- Ao recomendar uma ISO, escreva o nome e coloque logo depois o marcador [[iso:ID]] com o id da lista (o site transforma isso em botão de download).\n"
    . "- Se nenhuma ISO servir, explique o motivo com honestidade. Não dê links de download: o site libera o arquivo depois de um formulário.\n"
    . "- Responda também dúvidas simples sobre o que é uma ISO e como instalar (gravar em pendrive e iniciar o computador por ele), sem sair desse tema.\n\n"
    . "ISOs disponíveis na biblioteca:\n" . implode("\n", $linhas);

// ---- Chamada à API da Anthropic ----
$url = $config['ANTHROPIC_API_URL'] ?? 'https://api.anthropic.com/v1/messages';
$requisicao = json_encode([
    'model' => $config['ANTHROPIC_MODEL'] ?? 'claude-haiku-5-5',
    'max_tokens' => 700,
    'system' => $sistema,
    'messages' => $mensagens,
], JSON_UNESCAPED_UNICODE);

// Usa streams do PHP (file_get_contents) em vez de cURL, para não depender de extensão extra no XAMPP.
$contexto = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => ['content-type: application/json', 'x-api-key: ' . $chave, 'anthropic-version: 2023-06-01'],
    'content' => $requisicao,
    'timeout' => 40,
    'ignore_errors' => true, // devolve o corpo mesmo em respostas 4xx/5xx, para podermos registrar o erro
]]);
$retorno = @file_get_contents($url, false, $contexto);
$status = 0;
if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
    $status = (int) $m[1];
}

$resposta = is_string($retorno) ? json_decode($retorno, true) : null;
$texto = $resposta['content'][0]['text'] ?? null;

if ($status !== 200 || !is_string($texto)) {
    error_log('Falha na API da IA (HTTP ' . $status . '): ' . substr((string) $retorno, 0, 300));
    erro_json('O assistente está indisponível no momento. Tente novamente em instantes.', 502);
}

// ---- Extrai os marcadores [[iso:ID]]: só valem ids que existem no catálogo ----
$isos = [];
$texto = preg_replace_callback('/\s*\[\[iso:(\d+)\]\]/', function (array $m) use ($idsValidos, &$isos): string {
    $id = (int) $m[1];
    if (isset($idsValidos[$id]) && !isset($isos[$id])) {
        $isos[$id] = ['id' => $id, 'nome' => $idsValidos[$id]];
    }

    return '';
}, $texto);

responder_json(['resposta' => trim($texto), 'isos' => array_values($isos)]);
