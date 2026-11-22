<?php

declare(strict_types=1);

/**
 * Cadastro e edição de ISO (mesmo formulário). Grava em três tabelas:
 * sistemas_operacionais, imagens_iso e requisitos, dentro de uma transação —
 * ou grava tudo ou não grava nada.
 */

require_once __DIR__ . '/_comum.php';
exigir_login();

$conexao = getConnection();
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
$editando = $id !== false && $id !== null && $id > 0;

// Valores iniciais do formulário (vazios no cadastro, do banco na edição).
$dados = [
    'nome' => '', 'familia' => 'Linux', 'descricao' => '', 'perfil_recomendado' => '', 'leve' => '0',
    'versao' => '', 'arquitetura' => 'x64', 'tamanho_mb' => '', 'url_oficial' => '', 'url_download_direto' => '', 'arquivo_local' => '', 'localizacao_arquivo' => '', 'checksum_sha256' => '', 'ativo' => '1',
    'ram_min_mb' => '', 'ram_rec_mb' => '', 'cpu_ghz_min' => '', 'geracao_cpu_min' => '', 'armazenamento_min_gb' => '',
];

if ($editando && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $consulta = $conexao->prepare('
        SELECT so.nome, so.familia, so.descricao, so.perfil_recomendado, so.leve,
               i.versao, i.arquitetura, i.tamanho_mb, i.url_oficial, i.url_download_direto, i.arquivo_local, i.localizacao_arquivo, i.checksum_sha256, i.ativo,
               r.ram_min_mb, r.ram_rec_mb, r.cpu_ghz_min, r.geracao_cpu_min, r.armazenamento_min_gb
        FROM imagens_iso i
        INNER JOIN sistemas_operacionais so ON so.id = i.sistema_id
        INNER JOIN requisitos r ON r.iso_id = i.id
        WHERE i.id = :id
    ');
    $consulta->execute(['id' => $id]);
    $linha = $consulta->fetch();

    if ($linha === false) {
        http_response_code(404);
        exit('ISO não encontrada.');
    }

    foreach ($dados as $campo => $_) {
        $dados[$campo] = (string) ($linha[$campo] ?? '');
    }
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    foreach ($dados as $campo => $_) {
        $valor = $_POST[$campo] ?? '';
        $dados[$campo] = is_string($valor) ? trim($valor) : '';
    }

    // ---- Validação no servidor (o navegador não é confiável) ----
    if ($dados['nome'] === '' || mb_strlen($dados['nome']) > 150) {
        $erros[] = 'Informe o nome do sistema (até 150 caracteres).';
    }
    if (!in_array($dados['familia'], ['Linux', 'Windows', 'macOS', 'Outro'], true)) {
        $erros[] = 'Família inválida.';
    }
    if ($dados['descricao'] === '') {
        $erros[] = 'Informe a descrição.';
    }
    if ($dados['perfil_recomendado'] === '' || mb_strlen($dados['perfil_recomendado']) > 255) {
        $erros[] = 'Informe o perfil recomendado (até 255 caracteres).';
    }
    if ($dados['versao'] === '' || mb_strlen($dados['versao']) > 100) {
        $erros[] = 'Informe a versão (até 100 caracteres).';
    }
    if (!in_array($dados['arquitetura'], ['x86', 'x64', 'arm64'], true)) {
        $erros[] = 'Arquitetura inválida.';
    }
    if ($dados['url_oficial'] !== '' && (
        filter_var($dados['url_oficial'], FILTER_VALIDATE_URL) === false
        || !preg_match('#^https?://#i', $dados['url_oficial'])
        || mb_strlen($dados['url_oficial']) > 500
    )) {
        $erros[] = 'O link oficial deve começar com http:// ou https://.';
    }

    if ($dados['url_download_direto'] !== '' && (
        filter_var($dados['url_download_direto'], FILTER_VALIDATE_URL) === false
        || !preg_match('#^https?://#i', $dados['url_download_direto'])
        || mb_strlen($dados['url_download_direto']) > 500
    )) {
        $erros[] = 'O link direto deve começar com http:// ou https://.';
    }
    if ($dados['arquivo_local'] !== '' && !preg_match('/^[A-Za-z0-9._ -]+\.iso$/', $dados['arquivo_local'])) {
        $erros[] = 'O arquivo local deve ser apenas o nome do arquivo, terminado em .iso (sem pastas).';
    }
    if (mb_strlen($dados['localizacao_arquivo']) > 500) {
        $erros[] = 'A localização deve ter até 500 caracteres.';
    }
    if ($dados['checksum_sha256'] !== '' && !preg_match('/^[0-9a-fA-F]{64}$/', $dados['checksum_sha256'])) {
        $erros[] = 'O checksum SHA-256 deve ter exatamente 64 caracteres hexadecimais (0-9 e a-f).';
    }

    $inteiros = [
        'tamanho_mb' => ['Tamanho (MB)', 1, 1000000],
        'ram_min_mb' => ['RAM mínima (MB)', 16, 1048576],
        'ram_rec_mb' => ['RAM recomendada (MB)', 16, 1048576],
        'geracao_cpu_min' => ['Geração mínima do processador', 1, 30],
        'armazenamento_min_gb' => ['Armazenamento mínimo (GB)', 1, 65535],
    ];
    foreach ($inteiros as $campo => [$rotulo, $min, $max]) {
        $numero = filter_var($dados[$campo], FILTER_VALIDATE_INT);
        if ($numero === false || $numero < $min || $numero > $max) {
            $erros[] = "{$rotulo}: informe um número inteiro entre {$min} e {$max}.";
        }
    }

    $ghz = str_replace(',', '.', $dados['cpu_ghz_min']);
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $ghz) || (float) $ghz < 0.1 || (float) $ghz > 10) {
        $erros[] = 'Clock mínimo (GHz): informe um valor entre 0,1 e 10.';
    }

    if (!$erros && (int) $dados['ram_rec_mb'] < (int) $dados['ram_min_mb']) {
        $erros[] = 'A RAM recomendada não pode ser menor que a mínima.';
    }

    if (!$erros) {
        $leve = $dados['leve'] === '1' ? 1 : 0;
        $ativo = $dados['ativo'] === '1' ? 1 : 0;
        $urlOficial = $dados['url_oficial'] === '' ? null : $dados['url_oficial'];
        $urlDireto = $dados['url_download_direto'] === '' ? null : $dados['url_download_direto'];
        $arquivoLocal = $dados['arquivo_local'] === '' ? null : $dados['arquivo_local'];
        $localizacao = $dados['localizacao_arquivo'] === '' ? null : $dados['localizacao_arquivo'];
        $checksum = $dados['checksum_sha256'] === '' ? null : strtolower($dados['checksum_sha256']);

        $conexao->beginTransaction();
        try {
            if ($editando) {
                $busca = $conexao->prepare('SELECT sistema_id FROM imagens_iso WHERE id = :id');
                $busca->execute(['id' => $id]);
                $sistemaId = $busca->fetchColumn();
                if ($sistemaId === false) {
                    throw new RuntimeException('ISO não encontrada.');
                }

                $conexao->prepare('
                    UPDATE sistemas_operacionais
                    SET nome = :nome, familia = :familia, descricao = :descricao,
                        perfil_recomendado = :perfil, leve = :leve
                    WHERE id = :id
                ')->execute([
                    'nome' => $dados['nome'], 'familia' => $dados['familia'], 'descricao' => $dados['descricao'],
                    'perfil' => $dados['perfil_recomendado'], 'leve' => $leve, 'id' => $sistemaId,
                ]);

                $conexao->prepare('
                    UPDATE imagens_iso
                    SET versao = :versao, arquitetura = :arq, tamanho_mb = :tamanho, url_oficial = :url,
                        url_download_direto = :direto, arquivo_local = :arquivo,
                        localizacao_arquivo = :local, checksum_sha256 = :checksum, ativo = :ativo
                    WHERE id = :id
                ')->execute([
                    'versao' => $dados['versao'], 'arq' => $dados['arquitetura'], 'tamanho' => $dados['tamanho_mb'],
                    'url' => $urlOficial, 'direto' => $urlDireto, 'arquivo' => $arquivoLocal,
                    'local' => $localizacao, 'checksum' => $checksum, 'ativo' => $ativo, 'id' => $id,
                ]);

                $conexao->prepare('
                    UPDATE requisitos
                    SET ram_min_mb = :rmin, ram_rec_mb = :rrec, cpu_ghz_min = :ghz,
                        geracao_cpu_min = :ger, armazenamento_min_gb = :arm
                    WHERE iso_id = :id
                ')->execute([
                    'rmin' => $dados['ram_min_mb'], 'rrec' => $dados['ram_rec_mb'], 'ghz' => $ghz,
                    'ger' => $dados['geracao_cpu_min'], 'arm' => $dados['armazenamento_min_gb'], 'id' => $id,
                ]);
            } else {
                // Reaproveita o sistema se já existir um com o mesmo nome (ex.: nova versão do Ubuntu).
                $busca = $conexao->prepare('SELECT id FROM sistemas_operacionais WHERE nome = :nome LIMIT 1');
                $busca->execute(['nome' => $dados['nome']]);
                $sistemaId = $busca->fetchColumn();

                if ($sistemaId === false) {
                    $conexao->prepare('
                        INSERT INTO sistemas_operacionais (nome, familia, descricao, perfil_recomendado, leve)
                        VALUES (:nome, :familia, :descricao, :perfil, :leve)
                    ')->execute([
                        'nome' => $dados['nome'], 'familia' => $dados['familia'], 'descricao' => $dados['descricao'],
                        'perfil' => $dados['perfil_recomendado'], 'leve' => $leve,
                    ]);
                    $sistemaId = $conexao->lastInsertId();
                }

                $conexao->prepare('
                    INSERT INTO imagens_iso (sistema_id, versao, arquitetura, tamanho_mb, url_oficial, url_download_direto, arquivo_local,
                         localizacao_arquivo, checksum_sha256, ativo)
                    VALUES (:sistema, :versao, :arq, :tamanho, :url, :direto, :arquivo, :local, :checksum, :ativo)
                ')->execute([
                    'sistema' => $sistemaId, 'versao' => $dados['versao'], 'arq' => $dados['arquitetura'],
                    'tamanho' => $dados['tamanho_mb'], 'url' => $urlOficial, 'direto' => $urlDireto, 'arquivo' => $arquivoLocal,
                    'local' => $localizacao, 'checksum' => $checksum, 'ativo' => $ativo,
                ]);
                $novoId = $conexao->lastInsertId();

                $conexao->prepare('
                    INSERT INTO requisitos (iso_id, ram_min_mb, ram_rec_mb, cpu_ghz_min, geracao_cpu_min, armazenamento_min_gb)
                    VALUES (:iso, :rmin, :rrec, :ghz, :ger, :arm)
                ')->execute([
                    'iso' => $novoId, 'rmin' => $dados['ram_min_mb'], 'rrec' => $dados['ram_rec_mb'], 'ghz' => $ghz,
                    'ger' => $dados['geracao_cpu_min'], 'arm' => $dados['armazenamento_min_gb'],
                ]);
            }

            $conexao->commit();
            redirecionar('index.php?aviso=salvo');
        } catch (Throwable $erro) {
            $conexao->rollBack();
            error_log('Falha ao salvar ISO: ' . $erro->getMessage());
            $erros[] = 'Não foi possível salvar. Tente novamente.';
        }
    }
}

/** Escreve um campo de texto/número do formulário. */
function campo(string $rotulo, string $nome, array $dados, string $tipo = 'text', string $extra = ''): void
{
    echo '<div class="field"><label for="' . e($nome) . '">' . e($rotulo) . '</label>'
        . '<input type="' . e($tipo) . '" id="' . e($nome) . '" name="' . e($nome) . '" value="' . e($dados[$nome]) . '" ' . $extra . '></div>';
}

/** Escreve uma lista de seleção marcando a opção atual. */
function selecao(string $rotulo, string $nome, array $dados, array $opcoes): void
{
    echo '<div class="field"><label for="' . e($nome) . '">' . e($rotulo) . '</label><select id="' . e($nome) . '" name="' . e($nome) . '">';
    foreach ($opcoes as $valor => $texto) {
        $marcada = (string) $valor === $dados[$nome] ? ' selected' : '';
        echo '<option value="' . e((string) $valor) . '"' . $marcada . '>' . e($texto) . '</option>';
    }
    echo '</select></div>';
}

// Avisa se o arquivo informado realmente existe na pasta storage/isos/.
$statusArquivo = '';
if ($dados['arquivo_local'] !== '' && preg_match('/^[A-Za-z0-9._ -]+\.iso$/', $dados['arquivo_local'])) {
    $existe = is_file(__DIR__ . '/../storage/isos/' . $dados['arquivo_local']);
    $statusArquivo = '<p class="' . ($existe ? 'mensagem-ok' : 'mensagem-erro') . '">'
        . ($existe ? 'Arquivo encontrado em storage/isos/.' : 'Arquivo ainda não encontrado em storage/isos/: copie o .iso para essa pasta.')
        . '</p>';
}

iniciar_pagina($editando ? 'Editar ISO' : 'Nova ISO');
?>
<section class="form-card admin-card">
  <div class="form-header">
    <h1><?= $editando ? 'Editar ISO' : 'Cadastrar nova ISO' ?></h1>
  </div>

  <?php if ($erros): ?>
    <div class="mensagem-erro" role="alert"><ul><?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <?= campo_csrf() ?>
    <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <fieldset class="admin-grupo">
      <legend>Sistema operacional</legend>
      <div class="form-grid">
        <?php
        campo('Nome', 'nome', $dados, 'text', 'maxlength="150" required');
        selecao('Família', 'familia', $dados, ['Linux' => 'Linux', 'Windows' => 'Windows', 'macOS' => 'macOS', 'Outro' => 'Outro']);
        campo('Perfil recomendado', 'perfil_recomendado', $dados, 'text', 'maxlength="255" required');
        ?>
        <div class="field">
          <label for="descricao">Descrição</label>
          <textarea id="descricao" name="descricao" rows="3" required><?= e($dados['descricao']) ?></textarea>
        </div>
        <?php selecao('Sistema leve?', 'leve', $dados, ['0' => 'Não', '1' => 'Sim']); ?>
      </div>
    </fieldset>

    <fieldset class="admin-grupo">
      <legend>Imagem ISO</legend>
      <div class="form-grid">
        <?php
        campo('Versão', 'versao', $dados, 'text', 'maxlength="100" required');
        selecao('Arquitetura', 'arquitetura', $dados, ['x86' => 'x86 (32 bits)', 'x64' => 'x64 (64 bits)', 'arm64' => 'arm64']);
        campo('Tamanho (MB)', 'tamanho_mb', $dados, 'number', 'min="1" required');
        campo('Arquivo hospedado no servidor (opcional, nome do .iso dentro de storage/isos/)', 'arquivo_local', $dados, 'text', 'maxlength="255" spellcheck="false"');
        echo $statusArquivo;
        campo('Link direto para o arquivo .iso (opcional)', 'url_download_direto', $dados, 'url', 'maxlength="500"');
        campo('Página oficial de download (opcional, usada se não houver arquivo nem link direto)', 'url_oficial', $dados, 'url', 'maxlength="500"');
        campo('Localização do arquivo (opcional, ex.: Site oficial do Ubuntu)', 'localizacao_arquivo', $dados, 'text', 'maxlength="500"');
        campo('Checksum SHA-256 (opcional, 64 caracteres)', 'checksum_sha256', $dados, 'text', 'maxlength="64" spellcheck="false"');
        selecao('Status', 'ativo', $dados, ['1' => 'Ativa (aparece no site)', '0' => 'Inativa (oculta)']);
        ?>
      </div>
    </fieldset>

    <fieldset class="admin-grupo">
      <legend>Requisitos mínimos de hardware</legend>
      <div class="form-grid">
        <?php
        campo('RAM mínima (MB)', 'ram_min_mb', $dados, 'number', 'min="16" required');
        campo('RAM recomendada (MB)', 'ram_rec_mb', $dados, 'number', 'min="16" required');
        campo('Clock mínimo (GHz)', 'cpu_ghz_min', $dados, 'text', 'inputmode="decimal" required');
        campo('Geração mínima do processador', 'geracao_cpu_min', $dados, 'number', 'min="1" required');
        campo('Armazenamento mínimo (GB)', 'armazenamento_min_gb', $dados, 'number', 'min="1" required');
        ?>
      </div>
    </fieldset>

    <div class="actions">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="index.php" class="btn btn-secondary btn-link">Cancelar</a>
    </div>
  </form>
</section>
<?php encerrar_pagina();
