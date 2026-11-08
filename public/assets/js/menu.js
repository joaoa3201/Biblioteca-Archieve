function abrirMenu() {
  document.getElementById("sidebar").classList.add("aberto");
  document.getElementById("overlay").classList.add("ativo");
}

function fecharMenu() {
  document.getElementById("sidebar").classList.remove("aberto");
  document.getElementById("overlay").classList.remove("ativo");
}

document.addEventListener("DOMContentLoaded", () => {
  const fecharLabel = document.body.classList.contains("pagina-busca") ? "×" : "X";

  document.body.insertAdjacentHTML("afterbegin", `
    <button class="menu-btn" type="button" aria-label="Abrir menu">☰</button>
    <nav id="sidebar" class="sidebar" aria-label="Navegação principal">
      <button class="fechar-btn" type="button" aria-label="Fechar menu">${fecharLabel}</button>
      <a href="index.html">Biblioteca</a>
      <a href="buscar.html">Buscar ISO/OS</a>
      <a href="assistente.html">Assistente de IA</a>
    </nav>
    <div id="overlay" class="overlay"></div>
  `);

  document.querySelector(".menu-btn").addEventListener("click", abrirMenu);
  document.querySelector(".fechar-btn").addEventListener("click", fecharMenu);
  document.getElementById("overlay").addEventListener("click", fecharMenu);
});
