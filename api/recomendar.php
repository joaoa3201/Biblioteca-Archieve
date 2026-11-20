<?php

declare(strict_types=1);

/**
 * GET /api/recomendar.php
 * Recomenda ISOs compatíveis com o hardware informado pelo usuário.
 *
 * Parâmetros: ram_mb, ghz, geracao e modelo (Intel Core Ultra, Intel Core,
 * AMD Ryzen ou Outro).
 *
 * Regra de compatibilidade (feita no banco, comparando com a tabela requisitos):
 *   - a ISO só é recomendada se o PC atende RAM mínima, clock mínimo e
 *     geração mínima do processador;
 *   - todos os modelos do formulário são processadores x86, então ISOs
 *     arm64 (ex.: macOS M4) são descartadas;
 *   - nível "ideal": RAM >= recomendada (roda com folga);
 *     nível "minimo": atende só o mínimo (roda no limite).
 * Ordenação: primeiro as ideais; dentro de cada nível, as mais modernas
 * (maior RAM recomendada) que o computador comporta.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '_helpers.php';

exigir_metodo_get();
validar_parametros_permitidos(['ram_mb', 'ghz', 'geracao', 'modelo']);

$ramMb = validar_inteiro($_GET['ram_mb'] ?? null, 64, 1048576);
$ghz = validar_decimal($_GET['ghz'] ?? null, 0.1, 10);
$geracao = validar_inteiro($_GET['geracao'] ?? null, 1, 30);
$modelo = validar_enum(
    validar_string($_GET['modelo'] ?? null, 100, true),
    ['Intel Core Ultra', 'Intel Core', 'AMD Ryzen', 'Outro']
);

$sql = consulta_base_iso() . '
    AND r.ram_min_mb <= :ram_min
    AND r.cpu_ghz_min <= :ghz
    AND r.geracao_cpu_min <= :geracao
    AND i.arquitetura <> \'arm64\'
    ORDER BY (r.ram_rec_mb <= :ram_rec) DESC, r.ram_rec_mb DESC, so.nome ASC
    LIMIT 8
';

try {
    $statement = obter_conexao_api()->prepare($sql);
    $statement->execute([
        'ram_min' => $ramMb,
        'ghz' => $ghz,
        'geracao' => $geracao,
        'ram_rec' => $ramMb,
    ]);

    $resultado = array_map(function (array $linha) use ($ramMb): array {
        $iso = normalizar_iso($linha);
        $iso['nivel'] = $iso['ram_rec_mb'] <= $ramMb ? 'ideal' : 'minimo';

        return $iso;
    }, $statement->fetchAll());

    responder_json($resultado);
} catch (Throwable $erro) {
    error_log('Falha ao recomendar ISOs: ' . $erro->getMessage());
    erro_json('Erro interno', 500);
}
