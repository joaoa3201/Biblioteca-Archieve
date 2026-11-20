<?php

declare(strict_types=1);

/**
 * Cria (ou atualiza a senha de) um usuário do painel administrativo.
 * Uso, no terminal:  php database/criar_admin.php <usuario> <senha>
 *
 * A senha é guardada apenas como hash (password_hash); a senha em si nunca
 * fica no banco nem no código.
 */

if (PHP_SAPI !== 'cli') {
    exit('Execute este script pelo terminal.');
}

if ($argc !== 3) {
    exit("Uso: php database/criar_admin.php <usuario> <senha>\n");
}

[, $usuario, $senha] = $argv;

if ($usuario === '' || strlen($usuario) > 100 || strlen($senha) < 8) {
    exit("Informe um usuário (até 100 caracteres) e uma senha com pelo menos 8 caracteres.\n");
}

require_once __DIR__ . '/../config/database.php';

$conexao = getConnection();
$conexao->prepare('
    INSERT INTO usuarios_admin (usuario, senha_hash) VALUES (:usuario, :hash)
    ON DUPLICATE KEY UPDATE senha_hash = VALUES(senha_hash)
')->execute([
    'usuario' => $usuario,
    'hash' => password_hash($senha, PASSWORD_DEFAULT),
]);

echo "Usuário '{$usuario}' salvo. Acesse /Biblioteca-Archieve/admin/\n";
