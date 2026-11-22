/**
 * Funções de formatação usadas em várias páginas, para mostrar os requisitos
 * das ISOs sempre do mesmo jeito (em português e em linguagem simples).
 */
(function () {
  "use strict";

  // Converte megabytes em texto: 512 -> "512 MB", 2048 -> "2 GB".
  function formatarMemoria(valorMb) {
    if (valorMb < 1024) {
      return `${valorMb} MB`;
    }

    return `${new Intl.NumberFormat("pt-BR", { maximumFractionDigits: 1 }).format(valorMb / 1024)} GB`;
  }

  // Ex.: "a partir de 1,5 GHz e 2ª geração".
  function formatarProcessador(iso) {
    const ghz = new Intl.NumberFormat("pt-BR", { maximumFractionDigits: 2 }).format(iso.cpu_ghz_min);
    return `a partir de ${ghz} GHz e ${iso.geracao_cpu_min}ª geração`;
  }

  // Lista única de requisitos (nome, valor) para cartões e páginas de detalhe.
  function listarRequisitos(iso) {
    return [
      ["Memória RAM mínima", formatarMemoria(iso.ram_min_mb)],
      ["Memória RAM recomendada", formatarMemoria(iso.ram_rec_mb)],
      ["Processador", formatarProcessador(iso)],
      ["Espaço em disco", `${iso.armazenamento_min_gb} GB`]
    ];
  }

  // Onde o arquivo é obtido: o texto cadastrado ou, se não houver, uma descrição padrão.
  function textoLocalizacao(iso) {
    if (iso.localizacao) return iso.localizacao;
    return iso.tem_link_oficial ? "Site oficial do sistema" : "Não informada";
  }

  window.textoLocalizacao = textoLocalizacao;
  window.formatarMemoria = formatarMemoria;
  window.formatarProcessador = formatarProcessador;
  window.listarRequisitos = listarRequisitos;
})();
