<?php

declare(strict_types=1);

/** Lista todas as ISOs (ativas e inativas) com os botões de editar e excluir. */

require_once __DIR__ . '/_comum.php';
exigir_login();

$isos = getConnection()->query('
    SELECT i.id, so.nome, so.familia, i.versao, i.arquitetura, i.tamanho_mb, i.ativo,
           (i.url_oficial IS NOT NULL AND i.url_oficial <> "") AS tem_link
    FROM imagens_iso i
    INNER JOIN sistemas_operacionais so ON so.id = i.sistema_id
    ORDER BY so.nome ASC, i.versao ASC
')->fetchAll();

$aviso = is_string($_GET['aviso'] ?? null) ? $_GET['aviso'] : '';
$mensagens = [
    'salvo' => 'ISO salva com sucesso.',
    'excluida' => 'ISO excluída.',
    'desativada' => 'Esta ISO tem solicitações de download registradas e não pode ser excluída; ela foi desativada.',
];

iniciar_pagina('ISOs');
?>
<section class="form-card admin-card">
  <div class="form-header">
    <h1>ISOs cadastradas</h1>
    <a class="btn btn-primary btn-link" href="form.php">Cadastrar nova ISO</a>
  </div>
  <?php if (isset($mensagens[$aviso])): ?><p class="mensagem-ok" role="status"><?= e($mensagens[$aviso]) ?></p><?php endif; ?>

  <div class="tabela-rolagem">
    <table class="tabela-admin">
      <thead>
        <tr><th>Sistema</th><th>Família</th><th>Versão</th><th>Arq.</th><th>Tamanho</th><th>Link</th><th>Status</th><th>Ações</th></tr>
      </thead>
      <tbody>
      <?php foreach ($isos as $iso): ?>
        <tr>
          <td><?= e($iso['nome']) ?></td>
          <td><?= e($iso['familia']) ?></td>
          <td><?= e($iso['versao']) ?></td>
          <td><?= e($iso['arquitetura']) ?></td>
          <td><?= (int) $iso['tamanho_mb'] ?> MB</td>
          <td><?= $iso['tem_link'] ? 'Sim' : 'Não' ?></td>
          <td><?= $iso['ativo'] ? 'Ativa' : 'Inativa' ?></td>
          <td class="acoes-tabela">
            <a href="form.php?id=<?= (int) $iso['id'] ?>">Editar</a>
            <form method="post" action="excluir.php"
                  onsubmit="return confirm('Excluir esta ISO? Esta ação não pode ser desfeita.');">
              <?= campo_csrf() ?>
              <input type="hidden" name="id" value="<?= (int) $iso['id'] ?>">
              <button type="submit" class="link-perigo">Excluir</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php encerrar_pagina();
