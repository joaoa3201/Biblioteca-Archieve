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
  const textoLiberado = document.getElementById("textoLiberado");

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
    resumo.textContent = `${iso.versao} • ${iso.arquitetura} • ${iso.tamanho_mb} MB • ${window.textoLocalizacao(iso)}`;

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

    // Código de verificação (SHA-256): permite conferir se o arquivo baixado não foi alterado.
    if (iso.checksum_sha256) {
      const checksum = document.getElementById("checksumIso");
      checksum.textContent = `Código de verificação (SHA-256): ${iso.checksum_sha256}. Use-o para conferir se o arquivo baixado está íntegro.`;
      checksum.hidden = false;
    }

    if (!iso.download_disponivel) {
      resumo.textContent += " — o download desta ISO ainda não está disponível.";
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
      const { tipo, url } = await window.solicitarDownload({
        iso_id: iso.id,
        nome: formulario.nome.value.trim(),
        email: formulario.email.value.trim(),
        objetivo: formulario.objetivo.value.trim()
      });

      linkDownload.href = url;
      formulario.hidden = true;
      areaLink.hidden = false;

      if (tipo === "pagina") {
        // Sem arquivo direto: o botão abre a página oficial de download do sistema.
        textoLiberado.textContent = "Este sistema é baixado na página do site oficial.";
        linkDownload.textContent = "Ir para o site oficial";
        linkDownload.target = "_blank";
        linkDownload.rel = "noopener noreferrer";
      } else {
        // Arquivo direto: o download começa sozinho; o botão serve caso o navegador bloqueie.
        textoLiberado.textContent = "Seu download vai começar automaticamente. Se não começar, use o botão abaixo.";
        linkDownload.removeAttribute("target");
        window.location.href = url;
      }
      linkDownload.focus();
    } catch (erro) {
      mostrarErro(erro.message);
      botaoEnviar.disabled = false;
      botaoEnviar.textContent = "Liberar download";
    }
  });
});
