<?php

declare(strict_types=1);

/** Tela de login do painel. A senha é conferida com password_verify() contra o hash do banco. */

require_once __DIR__ . '/_comum.php';

if (!empty($_SESSION['admin_id'])) {
    redirecionar('index.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $usuario = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
    $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';

    $consulta = getConnection()->prepare('SELECT id, senha_hash FROM usuarios_admin WHERE usuario = :usuario');
    $consulta->execute(['usuario' => $usuario]);
    $admin = $consulta->fetch();

    // Mesma mensagem para usuário ou senha incorretos, para não revelar quais usuários existem.
    if ($admin !== false && password_verify($senha, $admin['senha_hash'])) {
        session_regenerate_id(true); // evita fixação de sessão
        $_SESSION['admin_id'] = (int) $admin['id'];
        redirecionar('index.php');
    }

    usleep(500000); // atraso simples para dificultar tentativas em sequência
    $erro = 'Usuário ou senha incorretos.';
}

iniciar_pagina('Entrar', false);
?>
<section class="form-card">
  <div class="form-header">
    <span class="badge">Biblioteca Archive</span>
    <h1>Painel administrativo</h1>
  </div>
  <form method="post">
    <?= campo_csrf() ?>
    <div class="form-grid">
      <div class="field">
        <label for="usuario">Usuário</label>
        <input type="text" id="usuario" name="usuario" required autocomplete="username">
      </div>
      <div class="field">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required autocomplete="current-password">
      </div>
    </div>
    <?php if ($erro !== ''): ?><p class="mensagem-erro" role="alert"><?= e($erro) ?></p><?php endif; ?>
    <div class="actions"><button type="submit" class="btn btn-primary">Entrar</button></div>
  </form>
</section>
<?php encerrar_pagina();
