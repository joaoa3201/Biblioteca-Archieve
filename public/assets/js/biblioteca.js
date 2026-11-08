document.addEventListener("DOMContentLoaded", () => {
  const campoBusca = document.getElementById("filtroBusca");
  const seletorFamilia = document.getElementById("filtroFamilia");
  const seletorArquitetura = document.getElementById("filtroArquitetura");
  const checkboxLeve = document.getElementById("filtroLeve");
  const contagem = document.getElementById("contagemResultados");
  const container = document.getElementById("catalogoResultado");

  let controladorAtual = null;
  let temporizadorBusca = null;
  let filtrosDisponiveis = false;
  const controles = [campoBusca, seletorFamilia, seletorArquitetura, checkboxLeve];
  controles.forEach((controle) => {
    controle.disabled = true;
  });

  function definirEstado(mensagem, classe = "catalogo-estado") {
    container.replaceChildren();
    const estado = document.createElement("p");
    estado.className = classe;
    estado.textContent = mensagem;
    container.append(estado);
    container.setAttribute("aria-busy", "false");
  }

  function adicionarOpcao(seletor, valor, texto) {
    const opcao = document.createElement("option");
    opcao.value = valor;
    opcao.textContent = texto;
    seletor.append(opcao);
  }

  function preencherOpcoes(seletor, valores) {
    valores.forEach((valor) => adicionarOpcao(seletor, valor, valor));
  }

  function formatarMemoria(valorMb) {
    if (valorMb < 1024) {
      return `${valorMb} MB`;
    }

    return `${new Intl.NumberFormat("pt-BR", {
      maximumFractionDigits: 1
    }).format(valorMb / 1024)} GB`;
  }

  function formatarTamanhoIso(valorMb) {
    if (valorMb < 1024) {
      return `${valorMb} MB`;
    }

    return `${new Intl.NumberFormat("pt-BR", {
      maximumFractionDigits: 1
    }).format(valorMb / 1024)} GB`;
  }

  function adicionarItem(lista, rotulo, valor) {
    const item = document.createElement("li");
    const titulo = document.createElement("strong");
    titulo.textContent = `${rotulo}: `;
    item.append(titulo, document.createTextNode(valor));
    lista.append(item);
  }

  function criarCard(iso) {
    const card = document.createElement("article");
    card.className = "catalog-card";

    const cabecalhoCard = document.createElement("div");
    cabecalhoCard.className = "catalog-card-tags";

    const tagFamilia = document.createElement("span");
    tagFamilia.className = "tag";
    tagFamilia.textContent = iso.familia;
    cabecalhoCard.append(tagFamilia);

    if (iso.leve) {
      const seloLeve = document.createElement("span");
      seloLeve.className = "selo-leve";
      seloLeve.textContent = "Leve";
      cabecalhoCard.append(seloLeve);
    }

    const titulo = document.createElement("h3");
    titulo.textContent = `${iso.nome} — ${iso.versao}`;

    const descricao = document.createElement("p");
    descricao.textContent = iso.descricao;

    const informacoes = document.createElement("ul");
    informacoes.className = "card-info";
    adicionarItem(informacoes, "Arquitetura", iso.arquitetura);
    adicionarItem(informacoes, "Tamanho", formatarTamanhoIso(iso.tamanho_mb));
    adicionarItem(informacoes, "RAM mínima", formatarMemoria(iso.ram_min_mb));
    adicionarItem(informacoes, "RAM recomendada", formatarMemoria(iso.ram_rec_mb));
    adicionarItem(informacoes, "Armazenamento", `${iso.armazenamento_min_gb} GB`);
    adicionarItem(informacoes, "Perfil recomendado", iso.perfil_recomendado);

    const areaAcao = document.createElement("div");
    areaAcao.className = "catalog-card-acao";

    const botaoBaixar = document.createElement("button");
    botaoBaixar.type = "button";
    botaoBaixar.className = "mini-btn";
    botaoBaixar.textContent = "Baixar";

    const avisoDownload = document.createElement("span");
    avisoDownload.className = "aviso-download";
    avisoDownload.setAttribute("role", "status");
    avisoDownload.setAttribute("aria-live", "polite");

    const downloadDisponivel = false;
    botaoBaixar.addEventListener("click", () => {
      if (downloadDisponivel) {
        window.location.href = `download.html?id=${encodeURIComponent(iso.id)}`;
        return;
      }

      avisoDownload.textContent = "Em breve";
    });

    areaAcao.append(botaoBaixar, avisoDownload);
    card.append(cabecalhoCard, titulo, descricao, informacoes, areaAcao);
    return card;
  }

  function atualizarContagem(total) {
    contagem.textContent = total === 1
      ? "1 sistema encontrado"
      : `${total} sistemas encontrados`;
  }

  function montarFiltros() {
    const filtros = {};
    const busca = campoBusca.value.trim();

    if (busca) {
      filtros.busca = busca;
    }
    if (seletorFamilia.value) {
      filtros.familia = seletorFamilia.value;
    }
    if (seletorArquitetura.value) {
      filtros.arquitetura = seletorArquitetura.value;
    }
    if (checkboxLeve.checked) {
      filtros.leve = 1;
    }

    return filtros;
  }

  async function carregarIsos(filtros = montarFiltros()) {
    if (controladorAtual) {
      controladorAtual.abort();
    }

    const controlador = new AbortController();
    controladorAtual = controlador;
    definirEstado("Carregando sistemas...");
    container.setAttribute("aria-busy", "true");
    contagem.textContent = "";

    try {
      const isos = await buscarIsos(filtros, controlador.signal);

      if (controlador.signal.aborted || controladorAtual !== controlador) {
        return;
      }

      container.replaceChildren();
      container.setAttribute("aria-busy", "false");

      if (isos.length === 0) {
        atualizarContagem(0);
        mostrarListaVazia();
        return;
      }

      atualizarContagem(isos.length);
      isos.forEach((iso) => container.append(criarCard(iso)));
    } catch (erro) {
      if (erro.name === "AbortError" || controladorAtual !== controlador) {
        return;
      }

      contagem.textContent = "";
      mostrarErro(erro.message);
    }
  }

  function mostrarListaVazia() {
    container.replaceChildren();
    container.setAttribute("aria-busy", "false");

    const mensagem = document.createElement("p");
    mensagem.className = "catalogo-estado";
    mensagem.textContent = "Nenhum sistema encontrado com esses filtros";

    const botaoLimpar = document.createElement("button");
    botaoLimpar.type = "button";
    botaoLimpar.className = "botao-estado";
    botaoLimpar.textContent = "Limpar filtros";
    botaoLimpar.addEventListener("click", limparFiltros);

    container.append(mensagem, botaoLimpar);
  }

  function mostrarErro(mensagemErro) {
    container.replaceChildren();
    container.setAttribute("aria-busy", "false");

    const mensagem = document.createElement("p");
    mensagem.className = "catalogo-estado";
    mensagem.textContent = mensagemErro;

    const botaoTentarNovamente = document.createElement("button");
    botaoTentarNovamente.type = "button";
    botaoTentarNovamente.className = "botao-estado";
    botaoTentarNovamente.textContent = "Tentar novamente";
    botaoTentarNovamente.addEventListener("click", () => {
      if (!filtrosDisponiveis) {
        iniciarCatalogo();
        return;
      }

      carregarIsos();
    });

    container.append(mensagem, botaoTentarNovamente);
  }

  function limparFiltros() {
    campoBusca.value = "";
    seletorFamilia.value = "";
    seletorArquitetura.value = "";
    checkboxLeve.checked = false;
    carregarIsos();
    campoBusca.focus();
  }

  async function iniciarCatalogo() {
    if (controladorAtual) {
      controladorAtual.abort();
    }

    const controlador = new AbortController();
    controladorAtual = controlador;
    definirEstado("Carregando sistemas...");
    container.setAttribute("aria-busy", "true");
    contagem.textContent = "";

    try {
      const [filtros, isos] = await Promise.all([
        buscarFamilias(),
        buscarIsos({}, controlador.signal)
      ]);

      if (controlador.signal.aborted || controladorAtual !== controlador) {
        return;
      }

      preencherOpcoes(seletorFamilia, filtros.familias);
      const arquiteturas = [...new Set(isos.map((iso) => iso.arquitetura))].sort();
      preencherOpcoes(seletorArquitetura, arquiteturas);
      filtrosDisponiveis = true;
      controles.forEach((controle) => {
        controle.disabled = false;
      });

      container.replaceChildren();
      container.setAttribute("aria-busy", "false");
      atualizarContagem(isos.length);
      if (isos.length === 0) {
        mostrarListaVazia();
        return;
      }
      isos.forEach((iso) => container.append(criarCard(iso)));
    } catch (erro) {
      if (erro.name === "AbortError" || controladorAtual !== controlador) {
        return;
      }

      mostrarErro(erro.message);
    }
  }

  campoBusca.addEventListener("input", () => {
    window.clearTimeout(temporizadorBusca);
    temporizadorBusca = window.setTimeout(() => carregarIsos(), 300);
  });

  seletorFamilia.addEventListener("change", () => carregarIsos());
  seletorArquitetura.addEventListener("change", () => carregarIsos());
  checkboxLeve.addEventListener("change", () => carregarIsos());

  iniciarCatalogo();
});
