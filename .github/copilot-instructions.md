# Biblioteca Archive — contexto do projeto

TCC de Engenharia de Software (ENIAC): plataforma web que disponibiliza imagens ISO de sistemas operacionais para reaproveitar computadores antigos, reduzir lixo eletrônico e dar inclusão tecnológica a usuários leigos.

## Stack (obrigatória)
- Front-end: HTML, CSS e JavaScript Vanilla (sem frameworks, sem npm)
- Back-end: PHP 8 puro (PDO, sem Composer/frameworks)
- Banco: MySQL (MariaDB do XAMPP)
- Ambiente: XAMPP, projeto em htdocs/Biblioteca-Archive

## Funcionalidades alvo
1. Biblioteca de ISOs (nome, versão, arquitetura, tamanho, localização, requisitos) com busca por SO e versão
2. Buscar ISO/OS: formulário de hardware (RAM, clock, geração, modelo, armazenamento) que recomenda ISOs compatíveis
3. Formulário antes do download, depois libera o acesso ao arquivo
4. Chatbot de IA consultivo: pergunta o hardware ao usuário leigo e recomenda ISOs do banco
5. Painel admin: cadastrar, editar, excluir ISOs

## Regras
- Todo texto de interface e comentários em português do Brasil
- Interface simples, acessível e pensada para quem tem pouco conhecimento técnico
- Visual atual: tema escuro com vermelho (manter identidade de public/assets/css/style.css)
- Nunca colocar chaves de API ou senhas no JavaScript; apenas em PHP lendo de config/.env
- Usar prepared statements em todo SQL; escapar toda saída HTML
- Mudanças pequenas e incrementais; explicar o que mudou ao final