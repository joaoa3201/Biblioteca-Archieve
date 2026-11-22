/**
 * Menu lateral compartilhado: é injetado por JavaScript para não repetir o HTML em cada página.
 * Acessibilidade: funciona pelo teclado (Tab, Enter, Esc), informa o estado aberto/fechado
 * aos leitores de tela e devolve o foco ao botão ao fechar. Também cria o link
 * "Pular para o conteúdo", que permite ir direto ao assunto da página.
 */
let botaoMenu = null;

function abrirMenu() {
  document.getElementById("sidebar").classList.add("aberto");
  document.getElementById("overlay").classList.add("ativo");
  botaoMenu.setAttribute("aria-expanded", "true");
  document.querySelector("#sidebar a").focus();
}

function fecharMenu() {
  const menu = document.getElementById("sidebar");
  const estavaAberto = menu.classList.contains("aberto");

  menu.classList.remove("aberto");
  document.getElementById("overlay").classList.remove("ativo");
  botaoMenu.setAttribute("aria-expanded", "false");

  // Só devolve o foco se o menu estava aberto (evita roubar o foco ao carregar a página).
  if (estavaAberto) botaoMenu.focus();
}

document.addEventListener("DOMContentLoaded", () => {
  const fecharLabel = document.body.classList.contains("pagina-busca") ? "×" : "X";
  const paginaAtual = window.location.pathname.split("/").pop() || "index.html";

  const links = [
    ["index.html", "Biblioteca"],
    ["buscar.html", "Buscar ISO/OS"],
    ["assistente.html", "Assistente de IA"]
  ].map(([destino, texto]) => {
    const atual = destino === paginaAtual ? ' aria-current="page"' : "";
    return `<a href="${destino}"${atual}>${texto}</a>`;
  }).join("");

  document.body.insertAdjacentHTML("afterbegin", `
    <a class="pular-conteudo" href="#conteudo-principal">Pular para o conteúdo</a>
    <button class="menu-btn" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebar">☰</button>
    <nav id="sidebar" class="sidebar" aria-label="Navegação principal">
      <button class="fechar-btn" type="button" aria-label="Fechar menu">${fecharLabel}</button>
      ${links}
    </nav>
    <div id="overlay" class="overlay"></div>
  `);

  // Marca o conteúdo principal como destino do link "Pular para o conteúdo".
  const principal = document.querySelector("main");
  if (principal) {
    principal.id = "conteudo-principal";
    principal.tabIndex = -1;
  }

  botaoMenu = document.querySelector(".menu-btn");
  botaoMenu.addEventListener("click", abrirMenu);
  document.querySelector(".fechar-btn").addEventListener("click", fecharMenu);
  document.getElementById("overlay").addEventListener("click", fecharMenu);

  // Esc fecha o menu.
  document.addEventListener("keydown", (evento) => {
    if (evento.key === "Escape" && document.getElementById("sidebar").classList.contains("aberto")) {
      fecharMenu();
    }
  });
});
