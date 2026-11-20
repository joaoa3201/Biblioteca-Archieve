/**
 * Página de download: mostra a ISO escolhida (parâmetro ?id= da URL), valida o
 * formulário e envia à API. O link oficial só aparece depois que a solicitação
 * é registrada no banco.
 */
document.addEventListener("DOMContentLoaded", async () => {
  const formulario = document.getElementById("formDownload");
  const resumo = document.getElementById("resumoIso");
  const titulo = document.getElementById("tituloDownload");
  const mensagemErro = document.getElementById("mensagemErro");
  const botaoEnviar = document.getElementById("botaoEnviar");
  const areaLink = document.getElementById("areaLink");
  const linkDownload = document.getElementById("linkDownload");

  const id = Number(new URLSearchParams(window.location.search).get("id"));
  let iso = null;

  function mostrarErro(mensagem) {
    mensagemErro.textContent = mensagem;
    mensagemErro.hidden = false;
  }

  // Sem um id válido na URL não há o que baixar.
  if (!Number.isInteger(id) || id < 1) {
    resumo.textContent = "ISO não informada. Volte à biblioteca e escolha um sistema.";
    return;
  }

  try {
    iso = await window.buscarIso(id);
    titulo.textContent = `Download: ${iso.nome}`;
    resumo.textContent = `${iso.versao} • ${iso.arquitetura} • ${iso.tamanho_mb} MB`;

    // Requisitos de hardware, para o usuário conferir antes de baixar.
    const listaRequisitos = document.getElementById("listaRequisitos");
    window.listarRequisitos(iso).forEach(([rotulo, valor]) => {
      const item = document.createElement("li");
      const negrito = document.createElement("strong");
      negrito.textContent = `${rotulo}: `;
      item.append(negrito, valor);
      listaRequisitos.append(item);
    });
    document.getElementById("requisitosIso").hidden = false;

    if (!iso.tem_link_oficial) {
      resumo.textContent += " — o link oficial desta ISO ainda não está disponível.";
      return;
    }

    formulario.hidden = false;
  } catch (erro) {
    resumo.textContent = erro.message;
    return;
  }

  formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    mensagemErro.hidden = true;

    // A validação do navegador (campos required) é só conforto; o PHP valida de novo.
    if (!formulario.checkValidity()) {
      formulario.reportValidity();
      return;
    }

    botaoEnviar.disabled = true;
    botaoEnviar.textContent = "Enviando...";

    try {
      const url = await window.solicitarDownload({
        iso_id: iso.id,
        nome: formulario.nome.value.trim(),
        email: formulario.email.value.trim(),
        objetivo: formulario.objetivo.value.trim()
      });

      linkDownload.href = url;
      formulario.hidden = true;
      areaLink.hidden = false;
      linkDownload.focus();
    } catch (erro) {
      mostrarErro(erro.message);
      botaoEnviar.disabled = false;
      botaoEnviar.textContent = "Liberar download";
    }
  });
});
