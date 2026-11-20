/**
 * Página "Buscar ISO/OS": o usuário informa o hardware do computador e a API
 * (api/recomendar.php) devolve as ISOs compatíveis, comparando com os
 * requisitos cadastrados no banco de dados.
 */

const camposResumo = [
  { id: "memoria", label: "Memória", placeholder: "Digite aqui a memória" },
  { id: "Mhzproc", label: "MHz/GHz do processador", placeholder: "Digite o MHz ou GHz do processador" },
  { id: "Gerpro", label: "Geração do processador", placeholder: "Digite a geração do processador" },
  { id: "modproc", label: "Modelo do processador", placeholder: "Selecione o modelo", tipo: "select" }
];

// Mantém só dígitos e ponto, para o campo aceitar apenas números.
function limparFormatoCampo(valor) {
  return String(valor || "").replace(/[^0-9.]/g, "");
}

// Acrescenta a unidade ao valor mostrado no campo (ex.: 8 -> "8RAM").
function formatarCampo(id, valor) {
  const numero = limparFormatoCampo(valor);

  if (!numero) return "";

  const mapa = {
    memoria: "RAM",
    Mhzproc: "mhz",
    Gerpro: "°g"
  };

  return `${numero}${mapa[id] || ""}`;
}

// Ao sair do campo, guarda o número puro em data-raw e exibe o valor com unidade.
function aplicarFormatoFinalDoCampo(input) {
  const numero = limparFormatoCampo(input.value.trim());

  if (!numero) {
    input.dataset.raw = "";
    input.value = "";
    return;
  }

  input.dataset.raw = numero;
  input.value = formatarCampo(input.id, numero);
}

function obterValorNumerico(input) {
  const valor = input?.dataset?.raw ?? limparFormatoCampo(input?.value ?? "");
  return Number(valor) || 0;
}

document.querySelectorAll("#memoria, #Mhzproc, #Gerpro").forEach((input) => {
  input.addEventListener("blur", () => aplicarFormatoFinalDoCampo(input));
  input.addEventListener("input", () => {
    const valor = limparFormatoCampo(input.value);
    input.dataset.raw = valor;
    input.value = valor;
  });
});

// Mostra uma mensagem simples na área de resultados (vazio, carregando ou erro).
function mostrarMensagem(texto) {
  const container = document.getElementById("cardsResultado");
  const estado = document.createElement("div");
  const paragrafo = document.createElement("p");

  estado.className = "empty-state";
  estado.setAttribute("role", "status");
  paragrafo.textContent = texto;
  estado.append(paragrafo);
  container.replaceChildren(estado);
}

function criarItem(rotulo, valor, classe) {
  const item = document.createElement("li");
  const negrito = document.createElement("strong");
  negrito.textContent = `${rotulo}: `;
  item.append(negrito, valor);
  if (classe) item.className = classe;
  return item;
}

function formatarMemoria(mb) {
  return mb >= 1024 ? `${mb / 1024} GB` : `${mb} MB`;
}

// Monta um card por ISO. Usa textContent (nunca innerHTML) para escapar os dados vindos da API.
function criarCard(iso) {
  const card = document.createElement("article");
  card.className = "result-card";

  const tag = document.createElement("span");
  tag.className = "tag";
  tag.textContent = iso.familia;

  const titulo = document.createElement("h3");
  titulo.textContent = iso.nome;

  const descricao = document.createElement("p");
  descricao.textContent = iso.descricao;

  const info = document.createElement("ul");
  info.className = "card-info";
  info.append(
    criarItem("Versão", `${iso.versao} (${iso.arquitetura})`),
    criarItem("RAM mínima", formatarMemoria(iso.ram_min_mb)),
    criarItem("RAM recomendada", formatarMemoria(iso.ram_rec_mb)),
    criarItem(
      "Compatibilidade",
      iso.nivel === "ideal" ? "Roda com folga" : "Roda no limite",
      iso.nivel === "ideal" ? "nivel-ideal" : "nivel-minimo"
    )
  );

  card.append(tag, titulo, descricao, info);

  // O download passa pelo formulário; sem link oficial cadastrado, o botão fica indisponível.
  const botao = document.createElement("a");
  botao.className = "mini-btn";
  if (iso.tem_link_oficial) {
    botao.href = `download.html?id=${encodeURIComponent(iso.id)}`;
    botao.textContent = "Baixar";
  } else {
    botao.classList.add("mini-btn-indisponivel");
    botao.setAttribute("aria-disabled", "true");
    botao.textContent = "Link indisponível";
  }
  card.append(botao);

  return card;
}

function renderizarResultados(isos) {
  const container = document.getElementById("cardsResultado");

  if (isos.length === 0) {
    mostrarMensagem("Nenhuma ISO da biblioteca é compatível com esse computador.");
    return;
  }

  container.replaceChildren(...isos.map(criarCard));
}

function mostrarResumoFormulario() {
  const lista = document.querySelector("#resumoFinal .resumo-lista");

  lista.replaceChildren(
    ...camposResumo.map(({ id, label, placeholder, tipo }) => {
      const campo = document.getElementById(id);
      const valor = tipo === "select" ? campo.value : (campo.dataset.raw || campo.value.trim());

      const item = document.createElement("div");
      item.className = "resumo-item";
      const rotulo = document.createElement("span");
      rotulo.textContent = label;
      const conteudo = document.createElement("strong");
      conteudo.textContent = valor || placeholder;
      item.append(rotulo, conteudo);
      return item;
    })
  );

  document.getElementById("resumoFinal").hidden = false;
}

async function validarFormulario() {
  const memoriaInput = document.getElementById("memoria");
  const mhzInput = document.getElementById("Mhzproc");
  const geracaoInput = document.getElementById("Gerpro");
  const modeloInput = document.getElementById("modproc");

  const memoriaGb = obterValorNumerico(memoriaInput);
  const mhz = obterValorNumerico(mhzInput);
  const geracao = obterValorNumerico(geracaoInput);
  const modelo = modeloInput.value;

  if (!memoriaGb || !mhz || !geracao || !modelo) {
    if (!modelo) modeloInput.focus();
    return;
  }

  mostrarResumoFormulario();
  mostrarMensagem("Buscando ISOs compatíveis...");
  document.getElementById("resultados").scrollIntoView({ behavior: "smooth", block: "start" });

  try {
    const isos = await window.recomendarIsos({
      ramMb: Math.round(memoriaGb * 1024), // o campo é preenchido em GB; o banco guarda em MB
      ghz: Number((mhz >= 1000 ? mhz / 1000 : mhz).toFixed(2)), // aceita o clock em MHz ou GHz
      geracao: Math.round(geracao),
      modelo
    });
    renderizarResultados(isos);
  } catch (erro) {
    mostrarMensagem(erro.message);
  }
}

function limparResultados() {
  mostrarMensagem("Preencha as informações do computador para ver as ISOs que melhor se encaixam no seu perfil.");
  document.getElementById("resumoFinal").hidden = true;
  document.querySelector("#resumoFinal .resumo-lista").replaceChildren();
}

document.addEventListener("DOMContentLoaded", limparResultados);

document.getElementById("formBusca").addEventListener("reset", () => {
  setTimeout(limparResultados, 0);
});
