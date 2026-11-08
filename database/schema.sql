CREATE DATABASE IF NOT EXISTS biblioteca_archive
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE biblioteca_archive;

CREATE TABLE IF NOT EXISTS sistemas_operacionais (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(150) NOT NULL,
  familia ENUM('Linux', 'Windows', 'macOS', 'Outro') NOT NULL,
  descricao TEXT NOT NULL,
  perfil_recomendado VARCHAR(255) NOT NULL,
  leve BOOLEAN NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  INDEX idx_sistemas_nome (nome),
  INDEX idx_sistemas_familia (familia)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS imagens_iso (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sistema_id BIGINT UNSIGNED NOT NULL,
  versao VARCHAR(100) NOT NULL,
  arquitetura ENUM('x86', 'x64', 'arm64') NOT NULL,
  tamanho_mb INT UNSIGNED NOT NULL,
  url_oficial VARCHAR(500) NULL,
  localizacao_arquivo VARCHAR(500) NULL,
  checksum_sha256 CHAR(64) NULL,
  ativo BOOLEAN NOT NULL DEFAULT 1,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_imagens_versao (versao),
  CONSTRAINT fk_imagens_sistema
    FOREIGN KEY (sistema_id) REFERENCES sistemas_operacionais (id)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS requisitos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  iso_id BIGINT UNSIGNED NOT NULL,
  ram_min_mb INT UNSIGNED NOT NULL,
  ram_rec_mb INT UNSIGNED NOT NULL,
  cpu_ghz_min DECIMAL(4, 2) NOT NULL,
  geracao_cpu_min SMALLINT UNSIGNED NOT NULL,
  armazenamento_min_gb SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_requisitos_iso (iso_id),
  CONSTRAINT fk_requisitos_iso
    FOREIGN KEY (iso_id) REFERENCES imagens_iso (id)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS solicitacoes_download (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  iso_id BIGINT UNSIGNED NOT NULL,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(254) NOT NULL,
  objetivo TEXT NOT NULL,
  ip VARCHAR(45) NOT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_solicitacoes_iso
    FOREIGN KEY (iso_id) REFERENCES imagens_iso (id)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios_admin (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario VARCHAR(100) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_admin_usuario (usuario)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
