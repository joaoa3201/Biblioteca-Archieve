(function () {
  "use strict";

  const caminhoIsos = "../api/isos.php";
  const caminhoFamilias = "../api/familias.php";

  async function lerResposta(response, recurso) {
    let resposta;

    try {
      resposta = await response.json();
    } catch (erro) {
      throw new Error(`Não foi possível ler os dados de ${recurso}. Tente novamente.`);
    }

    if (
      !resposta ||
      typeof resposta !== "object" ||
      typeof resposta.sucesso !== "boolean" ||
      !Object.prototype.hasOwnProperty.call(resposta, "dados")
    ) {
      throw new Error(`A resposta de ${recurso} está inválida. Tente novamente.`);
    }

    if (!response.ok || !resposta.sucesso) {
      if (response.status >= 500) {
        throw new Error(`Não foi possível carregar ${recurso}. Tente novamente.`);
      }

      throw new Error(resposta.erro || `Não foi possível carregar ${recurso}.`);
    }

    return resposta.dados;
  }

  async function requisitar(url, recurso, sinal) {
    try {
      const response = await fetch(url, {
        method: "GET",
        headers: { Accept: "application/json" },
        signal: sinal
      });

      return await lerResposta(response, recurso);
    } catch (erro) {
      if (erro.name === "AbortError") {
        throw erro;
      }

      if (erro instanceof TypeError) {
        throw new Error(`Não foi possível conectar à API para carregar ${recurso}. Verifique sua conexão e tente novamente.`);
      }

      throw erro;
    }
  }

  async function buscarIsos(filtros = {}, sinal) {
    const parametros = new URLSearchParams();
    const camposTexto = ["busca", "familia", "arquitetura"];

    camposTexto.forEach((campo) => {
      const valor = filtros[campo];
      if (typeof valor === "string" && valor.trim() !== "" && valor !== "Todas") {
        parametros.set(campo, valor.trim());
      }
    });

    if (
      typeof filtros.leve === "boolean" &&
      filtros.leve
    ) {
      parametros.set("leve", "1");
    } else if (filtros.leve === 1 || filtros.leve === "1") {
      parametros.set("leve", "1");
    }

    const query = parametros.toString();
    const url = query ? `${caminhoIsos}?${query}` : caminhoIsos;
    const dados = await requisitar(url, "os sistemas", sinal);
    if (!Array.isArray(dados)) {
      throw new Error("A lista de sistemas recebida está inválida. Tente novamente.");
    }

    return dados;
  }

  async function buscarFamilias() {
    const dados = await requisitar(caminhoFamilias, "os filtros", undefined);
    if (
      !dados ||
      !Array.isArray(dados.familias) ||
      !Array.isArray(dados.versoes)
    ) {
      throw new Error("Os filtros recebidos estão inválidos. Tente novamente.");
    }

    return dados;
  }

  window.buscarIsos = buscarIsos;
  window.buscarFamilias = buscarFamilias;
})();
