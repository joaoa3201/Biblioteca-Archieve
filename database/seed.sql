USE biblioteca_archive;

-- Limpa apenas os dados de catálogo gerenciados por este seed. Solicitações
-- de download e contas administrativas são preservadas.
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM requisitos;
DELETE FROM imagens_iso;
DELETE FROM sistemas_operacionais;
SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;

-- A troca de Zorin OS Lite por Linux Mint XFCE evita cadastrar uma edição
-- cuja distribuição atual não foi confirmada.
INSERT INTO sistemas_operacionais
  (nome, familia, descricao, perfil_recomendado, leve)
VALUES
  ('Linux Mint', 'Linux', 'Ótimo para uso cotidiano, leve e intuitivo.', 'Ideal para desktop e estudos', 1),
  ('Ubuntu', 'Linux', 'Popular, estável e muito utilizado para trabalho e estudos.', 'Muito bom para produtividade', 0),
  ('Arch Linux', 'Linux', 'Customizável, voltado para usuários avançados.', 'Perfeito para configuração manual', 0),
  ('Kali Linux', 'Linux', 'Focado em testes de segurança e análise técnica.', 'Recomendado para ambiente técnico', 0),
  ('Pop!_OS', 'Linux', 'Leve, moderno e ótimo para jogos e produtividade.', 'Excelente para performance geral', 0),
  ('Windows XP', 'Windows', 'Versão antiga, indicada para computadores muito antigos.', 'Compatível com hardware legado', 1),
  ('Windows 7', 'Windows', 'Compatível com máquinas antigas e uso simples.', 'Bom para uso básico', 0),
  ('Windows 8', 'Windows', 'Interface moderna, boa para uso geral.', 'Compatível com setups moderados', 0),
  ('Windows 10', 'Windows', 'Versão mais equilibrada entre desempenho e compatibilidade.', 'Excelente para uso geral', 0),
  ('Windows 11', 'Windows', 'Versão atual, ideal para hardware recente. Exige TPM 2.0 e boot UEFI.', 'Melhor para desempenho moderno', 0),
  ('macOS High Sierra', 'macOS', 'Versão clássica da Apple, boa para hardware compatível. Sem imagem ISO oficial de distribuição; uso restrito a hardware Apple.', 'Boa para setups compatíveis', 0),
  ('macOS Lion', 'macOS', 'Sistema histórico, mais leve e simples. Sem imagem ISO oficial de distribuição; uso restrito a hardware Apple.', 'Indicada para uso antigo', 1),
  ('macOS M4', 'macOS', 'Versão moderna do ecossistema Apple com melhor desempenho. Sem imagem ISO oficial de distribuição; uso restrito a hardware Apple.', 'Ideal para hardware recente', 0),
  ('Lubuntu', 'Linux', 'Distribuição Ubuntu com ambiente leve, indicada para computadores modestos.', 'Boa opção para PCs antigos com poucos recursos', 1),
  ('Xubuntu', 'Linux', 'Distribuição Ubuntu com ambiente XFCE, adequada para máquinas modestas.', 'Uso cotidiano em PCs antigos ou intermediários', 1),
  ('antiX', 'Linux', 'Distribuição Linux muito leve, projetada para computadores antigos.', 'Reaproveitamento de hardware com poucos recursos', 1),
  ('Puppy Linux', 'Linux', 'Distribuição compacta que pode funcionar bem em equipamentos antigos.', 'PCs antigos com armazenamento e memória limitados', 1),
  ('Linux Mint XFCE', 'Linux', 'Edição do Linux Mint com ambiente XFCE, adequada para computadores modestos.', 'Migração simples para Linux em PCs modestos', 1),
  ('Debian XFCE', 'Linux', 'Debian com ambiente XFCE, combinando estabilidade e baixo consumo.', 'Uso geral em computadores modestos', 1);

INSERT INTO imagens_iso
  (sistema_id, versao, arquitetura, tamanho_mb, url_oficial, ativo)
VALUES
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Linux Mint'), 'Versão atual', 'x64', 2800, 'https://linuxmint.com/download.php', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Ubuntu'), 'LTS atual', 'x64', 6200, 'https://ubuntu.com/download/desktop', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Arch Linux'), 'Rolling release', 'x64', 1200, 'https://archlinux.org/download/', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Kali Linux'), 'Rolling release', 'x64', 4500, 'https://www.kali.org/get-kali/', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Pop!_OS'), 'Versão atual', 'x64', 3800, 'https://pop.system76.com/', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Windows XP'), 'SP3', 'x86', 600, NULL, 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Windows 7'), 'SP1', 'x64', 3200, NULL, 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Windows 8'), '8.1', 'x64', 3300, NULL, 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Windows 10'), '22H2', 'x64', 5500, 'https://www.microsoft.com/software-download/windows10', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Windows 11'), 'Versão atual', 'x64', 6500, 'https://www.microsoft.com/software-download/windows11', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'macOS High Sierra'), 'High Sierra', 'x64', 5200, NULL, 0),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'macOS Lion'), 'Lion', 'x64', 4500, NULL, 0),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'macOS M4'), 'M4', 'arm64', 15000, NULL, 0),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Lubuntu'), 'LTS atual', 'x64', 4000, 'https://lubuntu.me/downloads/', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Xubuntu'), 'LTS atual', 'x64', 4500, 'https://xubuntu.org/download/', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'antiX'), 'Versão atual', 'x64', 2200, 'https://antixlinux.com/download/', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Puppy Linux'), 'Versão atual', 'x64', 500, NULL, 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Linux Mint XFCE'), 'Versão atual', 'x64', 3500, 'https://linuxmint.com/download.php', 1),
  ((SELECT id FROM sistemas_operacionais WHERE nome = 'Debian XFCE'), 'Versão atual', 'x64', 3900, 'https://www.debian.org/distrib/', 1);

-- Estimativas mantidas: arquitetura e tamanho_mb das 19 ISOs, e
-- cpu_ghz_min e geracao_cpu_min dos 19 sistemas. RAM mínima/recomendada e
-- armazenamento também são estimados para Windows XP/7/8, macOS High Sierra,
-- Lion e M4, Lubuntu, Xubuntu, antiX, Puppy Linux, Linux Mint XFCE e Debian XFCE.
INSERT INTO requisitos
  (iso_id, ram_min_mb, ram_rec_mb, cpu_ghz_min, geracao_cpu_min, armazenamento_min_gb)
VALUES
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Linux Mint'), 2048, 4096, 1.00, 2, 20),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Ubuntu'), 4096, 8192, 2.00, 4, 25),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Arch Linux'), 512, 2048, 1.00, 2, 5),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Kali Linux'), 2048, 4096, 2.00, 4, 20),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Pop!_OS'), 4096, 8192, 2.00, 4, 20),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Windows XP'), 512, 1024, 0.30, 1, 5),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Windows 7'), 2048, 4096, 1.00, 2, 20),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Windows 8'), 2048, 4096, 1.00, 2, 20),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Windows 10'), 2048, 4096, 1.00, 4, 20),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Windows 11'), 4096, 8192, 1.00, 8, 64),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'macOS High Sierra'), 4096, 8192, 2.00, 4, 20),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'macOS Lion'), 2048, 2048, 1.80, 2, 7),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'macOS M4'), 16384, 16384, 2.00, 4, 30),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Lubuntu'), 1024, 2048, 1.00, 2, 10),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Xubuntu'), 2048, 4096, 1.00, 2, 15),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'antiX'), 512, 1024, 0.50, 1, 5),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Puppy Linux'), 512, 1024, 0.50, 1, 2),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Linux Mint XFCE'), 2048, 4096, 1.00, 2, 15),
  ((SELECT i.id FROM imagens_iso i JOIN sistemas_operacionais s ON s.id = i.sistema_id WHERE s.nome = 'Debian XFCE'), 1024, 2048, 1.00, 2, 10);

COMMIT;
