<?php

declare(strict_types=1);

/**
 * Funções comuns do painel administrativo: sessão, proteção CSRF,
 * escape de saída HTML e o layout (cabeçalho/rodapé) das páginas.
 */

require_once __DIR__ . '/../config/database.php';

session_set_cookie_params([
    'httponly' => true,       // o JavaScript não consegue ler o cookie de sessão
    'samesite' => 'Strict',   // o cookie não é enviado em requisições de outros sites
]);
session_start();

/** Escapa texto para exibição em HTML (evita XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redireciona para outra página do painel e encerra a execução. */
function redirecionar(string $destino): never
{
    header('Location: ' . $destino);
    exit;
}

/** Só deixa passar quem fez login; os demais vão para a tela de login. */
function exigir_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirecionar('login.php');
    }
}

/** Token anti-CSRF: todo formulário que altera dados precisa enviá-lo de volta. */
function token_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(token_csrf()) . '">';
}

/** Interrompe a requisição POST se o token não corresponder ao da sessão. */
function validar_csrf(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(token_csrf(), $enviado)) {
        http_response_code(403);
        exit('Requisição inválida. Volte e tente novamente.');
    }
}

function iniciar_pagina(string $titulo, bool $logado = true): void
{
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>' . e($titulo) . ' | Admin Biblioteca Archive</title>'
        . '<link rel="icon" href="../public/assets/img/livro.png" type="image/png">'
        . '<link rel="stylesheet" href="../public/assets/css/style.css">'
        . '<link rel="stylesheet" href="../public/assets/css/admin.css"></head>'
        . '<body class="pagina-busca pagina-admin"><main class="admin-conteudo">';

    if ($logado) {
        echo '<nav class="admin-nav"><a href="index.php">ISOs</a>'
            . '<form method="post" action="logout.php">' . campo_csrf()
            . '<button type="submit" class="btn btn-secondary">Sair</button></form></nav>';
    }
}

function encerrar_pagina(): void
{
    echo '</main></body></html>';
}
