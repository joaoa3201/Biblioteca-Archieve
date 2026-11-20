# Banco de dados

## Importação pelo phpMyAdmin do XAMPP

1. Inicie o Apache e o MySQL pelo painel de controle do XAMPP.
2. Acesse `http://localhost/phpmyadmin`.
3. Entre com o usuário `root`. No XAMPP, ele normalmente não tem senha.
4. Abra a guia **Importar**, selecione `database/schema.sql` e execute a importação. O script cria e seleciona o banco `biblioteca_archive`.
5. Na guia **Importar**, selecione `database/seed.sql` e execute-o para cadastrar os sistemas, imagens e requisitos iniciais.

O seed contém 19 entradas: os 13 sistemas do catálogo das páginas de biblioteca e busca, mais Lubuntu, Xubuntu, antiX, Puppy Linux, Zorin OS Lite e Debian XFCE. Os links oficiais que não puderam ser confirmados com segurança foram deixados como `NULL`.

## Configuração da conexão

Copie `config/.env.example` para `config/.env` e ajuste as credenciais se necessário. Com a configuração padrão do XAMPP, mantenha `DB_USER=root` e `DB_PASS=` (senha vazia). O arquivo `.env` é ignorado pelo Git e não deve ser compartilhado.

## Dados estimados para revisão

O catálogo JavaScript não informa versões, arquitetura, tamanho das imagens ISO nem requisitos de CPU, geração do processador, armazenamento e memória recomendada. Por isso, o seed usa `Não informada no catálogo` como versão para os itens sem versão declarada e traz valores conservadores estimados para esses campos. Para macOS M4, a versão foi mantida conforme o nome informado no catálogo, sem assumir um nome de versão do sistema.

As páginas oficiais de download foram preenchidas somente quando identificadas com segurança. Para XP, Windows 7/8 e as versões do macOS, `url_oficial` está `NULL`: a Microsoft e a Apple não oferecem mais download público oficial dessas imagens (a página da Microsoft para o Windows 8.1 retorna 404).

## Teste de conexão

Depois de importar o schema e o seed e criar `config/.env`, abra um terminal na pasta do projeto e execute:

```text
php database/teste_conexao.php
```

Se o comando `php` não estiver no PATH do Windows, use `C:\xampp\php\php.exe database\teste_conexao.php`.

O script é destinado à CLI e informa quantas imagens ISO há por família.

## Painel administrativo

1. Crie o primeiro administrador pelo terminal (a senha é gravada só como hash):

   ```text
   php database/criar_admin.php meu_usuario minha_senha_forte
   ```

2. Acesse `http://localhost/Biblioteca-Archieve/admin/` e entre com esse usuário.
3. No painel é possível cadastrar, editar e excluir ISOs. Uma ISO que já recebeu solicitações de download não é apagada, apenas desativada, para manter o histórico.

## Chatbot (assistente de IA)

O assistente usa a API da Anthropic. Para ativá-lo, crie uma chave em https://console.anthropic.com e preencha `ANTHROPIC_API_KEY` em `config/.env` (veja `config/.env.example`). A chave é lida somente pelo PHP; sem ela, a tela do assistente avisa que ele está indisponível. O uso da API é cobrado à parte.
