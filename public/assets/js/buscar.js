/**
 * Página "Buscar ISO/OS": o usuário informa o hardware do computador e a API
 * (api/recomendar.php) devolve as ISOs compatíveis, comparando com os
 * requisitos cadastrados no banco de dados.
 */

const camposResumo = [
  { id: "memoria", label: "Memória RAM", unidade: () => " GB", placeholder: "Não informada" },
  // Valores a partir de 1000 são entendidos como MHz; abaixo disso, como GHz.
  { id: "Mhzproc", label: "Velocidade do processador", unidade: (valor) => (Number(valor) >= 1000 ? " MHz" : " GHz"), placeholder: "Não informada" },
  { id: "Gerpro", label: "Geração do processador", unidade: () => "ª geração", placeholder: "Não informada" },
  { id: "modproc", label: "Marca do processador", placeholder: "Não informada", tipo: "select" }
];

// Mantém só dígitos e ponto, para o campo aceitar apenas números.
// A vírgula é aceita como separador decimal ("2,4" vira "2.4"), como se escreve no Brasil.
function limparFormatoCampo(valor) {
  const texto = String(valor || "").replace(",", ".").replace(/[^0-9.]/g, "");
  const [inteiro, ...decimais] = texto.split(".");
  return decimais.length ? `${inteiro}.${decimais.join("")}` : inteiro;
}

function obterValorNumerico(input) {
  return Number(limparFormatoCampo(input?.value ?? "")) || 0;
}

// A unidade de cada campo aparece no rótulo da tela; aqui o campo só aceita números.
document.querySelectorAll("#memoria, #Mhzproc, #Gerpro").forEach((input) => {
  input.addEventListener("input", () => {
    input.value = limparFormatoCampo(input.value);
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
    ...window.listarRequisitos(iso).map(([rotulo, valor]) => criarItem(rotulo, valor)),
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
  if (iso.download_disponivel) {
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
    ...camposResumo.map(({ id, label, unidade, placeholder, tipo }) => {
      const campo = document.getElementById(id);
      let valor = tipo === "select" ? campo.value : campo.value.trim();
      if (valor && unidade) valor = `${valor}${unidade(valor)}`;

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

  // Aviso acessível (role="alert"): leitores de tela anunciam o erro e o foco vai ao campo com problema.
  const erro = document.getElementById("erroBusca");
  const faltando = [
    [memoriaGb, memoriaInput, "a memória RAM"],
    [mhz, mhzInput, "a velocidade do processador"],
    [geracao, geracaoInput, "a geração do processador"],
    [modelo, modeloInput, "a marca do processador"]
  ].find(([valor]) => !valor);

  if (faltando) {
    erro.textContent = `Preencha ${faltando[2]} para continuar.`;
    erro.hidden = false;
    faltando[1].focus();
    return;
  }
  erro.hidden = true;

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
  document.getElementById("erroBusca").hidden = true;
  document.querySelector("#resumoFinal .resumo-lista").replaceChildren();
}

document.addEventListener("DOMContentLoaded", limparResultados);

document.getElementById("formBusca").addEventListener("reset", () => {
  setTimeout(limparResultados, 0);
});
