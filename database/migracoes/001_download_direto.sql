-- Acrescenta as duas formas de entregar a ISO ao usuário sem passar pelo site de terceiros.
-- Para bancos já criados: importe este arquivo no phpMyAdmin (bancos novos já vêm com as colunas no schema.sql).
USE biblioteca_archive;

ALTER TABLE imagens_iso
  ADD COLUMN IF NOT EXISTS url_download_direto VARCHAR(500) NULL AFTER url_oficial,
  ADD COLUMN IF NOT EXISTS arquivo_local VARCHAR(255) NULL AFTER url_download_direto;
