/**
 * Tela do chatbot. Guarda o histórico da conversa na memória da página e envia
 * tudo à API a cada pergunta (a API não guarda estado). O texto da IA é inserido
 * com textContent, nunca como HTML, para evitar XSS.
 */
document.addEventListener("DOMContentLoaded", () => {
  const areaMensagens = document.getElementById("chatMensagens");
  const formulario = document.getElementById("chatForm");
  const campoTexto = document.getElementById("chatTexto");
  const botaoEnviar = document.getElementById("chatEnviar");

  const historico = []; // [{ papel: "user" | "assistant", texto }]

  function adicionarMensagem(papel, texto, isos = []) {
    const bolha = document.createElement("div");
    bolha.className = `chat-bolha chat-${papel === "user" ? "usuario" : "assistente"}`;

    const conteudo = document.createElement("p");
    conteudo.textContent = texto;
    bolha.append(conteudo);

    // Cada ISO recomendada vira um botão que leva ao formulário de download.
    isos.forEach((iso) => {
      const link = document.createElement("a");
      link.className = "mini-btn chat-iso";
      link.href = `download.html?id=${encodeURIComponent(iso.id)}`;
      link.textContent = `Baixar ${iso.nome}`;
      bolha.append(link);
    });

    areaMensagens.append(bolha);
    areaMensagens.scrollTop = areaMensagens.scrollHeight;
    return bolha;
  }

  adicionarMensagem(
    "assistant",
    "Olá! Eu ajudo a escolher um sistema operacional para o seu computador. Quanta memória RAM ele tem e qual é o processador? Se não souber, pode me dizer a marca e o ano do computador."
  );

  formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();

    const texto = campoTexto.value.trim();
    if (!texto) return;

    adicionarMensagem("user", texto);
    historico.push({ papel: "user", texto });
    campoTexto.value = "";
    botaoEnviar.disabled = true;

    const aguardando = adicionarMensagem("assistant", "Pensando...");

    try {
      const { resposta, isos } = await window.enviarMensagemChat(historico);
      aguardando.remove();
      historico.push({ papel: "assistant", texto: resposta });
      adicionarMensagem("assistant", resposta, isos);
    } catch (erro) {
      aguardando.remove();
      historico.pop(); // permite reenviar a mesma pergunta
      adicionarMensagem("assistant", erro.message);
    } finally {
      botaoEnviar.disabled = false;
      campoTexto.focus();
    }
  });

  // Enter envia; Shift+Enter quebra a linha.
  campoTexto.addEventListener("keydown", (evento) => {
    if (evento.key === "Enter" && !evento.shiftKey) {
      evento.preventDefault();
      formulario.requestSubmit();
    }
  });
});
