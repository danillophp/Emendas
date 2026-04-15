# Entrega técnica por etapas
## 1. Atualizar modelagem do banco e gerar SQL de migração
### Arquivos
- `database/schema.sql`
- `sql/schema.sql`
- `database/update_2026_03_demandas_notificacoes.sql`
### Conteúdo completo dos arquivos
#### `database/schema.sql`
```sql
-- =========================================================
-- Sistema de Gestão de Emendas Governamentais
-- Banco: santo821_emenda
-- Compatível com MySQL 5.7+/8.0 e phpMyAdmin (HostGator)
-- =========================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET SQL_MODE = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- Criação do banco
-- ---------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `santo821_emenda`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `santo821_emenda`;

-- ---------------------------------------------------------
-- Criação do usuário de banco (quando permitido pelo host)
-- Em hospedagem compartilhada pode já existir via painel.
-- ---------------------------------------------------------
CREATE USER IF NOT EXISTS 'santo821_emenda'@'localhost' IDENTIFIED BY 'php@3903.';
GRANT ALL PRIVILEGES ON `santo821_emenda`.* TO 'santo821_emenda'@'localhost';
FLUSH PRIVILEGES;

-- ---------------------------------------------------------
-- Reimportação segura (ordem por dependência)
-- ---------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_demandas_ai_log`;
DROP TRIGGER IF EXISTS `trg_demandas_au_log`;
DROP TRIGGER IF EXISTS `trg_demandas_ad_log`;

DROP TABLE IF EXISTS `redefinicao_senha`;
DROP TABLE IF EXISTS `logs_sistema`;
DROP TABLE IF EXISTS `notificacoes`;
DROP TABLE IF EXISTS `demandas`;
DROP TABLE IF EXISTS `usuarios`;

-- ---------------------------------------------------------
-- 1) Tabela: usuarios
-- ---------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome_completo` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `endereco` VARCHAR(255) NOT NULL,
  `whatsapp` VARCHAR(20) NOT NULL,
  `numero_decreto` VARCHAR(50) NOT NULL,
  `data_nascimento` DATE NOT NULL,
  `usuario` VARCHAR(60) NOT NULL,
  `senha_hash` VARCHAR(255) NOT NULL,
  `perfil` ENUM('master','funcionario') NOT NULL,
  `primeiro_login` TINYINT(1) NOT NULL DEFAULT 1,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_login` DATETIME NULL,
  `session_token` VARCHAR(128) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  UNIQUE KEY `uq_usuarios_usuario` (`usuario`),
  UNIQUE KEY `uq_usuarios_numero_decreto` (`numero_decreto`),
  KEY `idx_usuarios_perfil_ativo` (`perfil`, `ativo`),
  KEY `idx_usuarios_ultimo_login` (`ultimo_login`),
  KEY `idx_usuarios_session_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 2) Tabela: demandas
-- ---------------------------------------------------------
CREATE TABLE `demandas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `emenda` VARCHAR(180) NOT NULL,
  `nome_politico` VARCHAR(150) NOT NULL,
  `tipo_processo` ENUM('prestacao_de_conta','cadastro_de_emenda') NOT NULL,
  `tipo_emenda` ENUM('parlamentar','estadual','municipal') NOT NULL,
  `data_prazo_resposta` DATETIME NOT NULL,
  `data_cadastro_emenda` DATE NOT NULL,
  `observacao` TEXT NULL,
  `anexo_emenda` VARCHAR(255) NULL,
  `anexo_nome_original` VARCHAR(180) NULL,
  `funcionario_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pendente','cadastrado') NOT NULL DEFAULT 'pendente',
  `criado_por` INT UNSIGNED NOT NULL,
  `data_ultima_atualizacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_demandas_status` (`status`),
  KEY `idx_demandas_prazo_resposta` (`data_prazo_resposta`),
  KEY `idx_demandas_funcionario_id` (`funcionario_id`),
  KEY `idx_demandas_criado_por` (`criado_por`),
  KEY `idx_demandas_status_prazo` (`status`, `data_prazo_resposta`),
  CONSTRAINT `fk_demandas_funcionario`
    FOREIGN KEY (`funcionario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_demandas_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 3) Tabela: notificacoes
-- ---------------------------------------------------------
CREATE TABLE `notificacoes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(120) NOT NULL,
  `mensagem` VARCHAR(255) NOT NULL,
  `tipo` VARCHAR(50) NOT NULL,
  `referencia_id` BIGINT UNSIGNED NULL,
  `referencia_tabela` VARCHAR(80) NULL,
  `lida` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notificacoes_usuario_lida` (`usuario_id`, `lida`),
  KEY `idx_notificacoes_tipo` (`tipo`),
  KEY `idx_notificacoes_referencia` (`referencia_tabela`, `referencia_id`),
  KEY `idx_notificacoes_created_at` (`created_at`),
  KEY `idx_notificacoes_automacao` (`usuario_id`, `tipo`, `referencia_id`, `created_at`),
  CONSTRAINT `fk_notificacoes_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 4) Tabela: logs_sistema
-- ---------------------------------------------------------
CREATE TABLE `logs_sistema` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NULL,
  `acao` VARCHAR(100) NOT NULL,
  `entidade` VARCHAR(80) NOT NULL,
  `entidade_id` BIGINT UNSIGNED NULL,
  `descricao` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_usuario_id` (`usuario_id`),
  KEY `idx_logs_entidade` (`entidade`, `entidade_id`),
  KEY `idx_logs_created_at` (`created_at`),
  CONSTRAINT `fk_logs_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 5) Tabela: redefinicao_senha
-- ---------------------------------------------------------
CREATE TABLE `redefinicao_senha` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(128) NOT NULL,
  `expira_em` DATETIME NOT NULL,
  `usado` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_redefinicao_token` (`token`),
  KEY `idx_redefinicao_usuario_id` (`usuario_id`),
  KEY `idx_redefinicao_expira_em` (`expira_em`),
  CONSTRAINT `fk_redefinicao_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Auditoria por trigger (demanda)
-- Uso opcional na aplicação: SET @app_user_id = <id_usuario_logado>;
-- ---------------------------------------------------------
DELIMITER $$

CREATE TRIGGER `trg_demandas_ai_log`
AFTER INSERT ON `demandas`
FOR EACH ROW
BEGIN
  INSERT INTO `logs_sistema` (`usuario_id`, `acao`, `entidade`, `entidade_id`, `descricao`)
  VALUES (
    COALESCE(@app_user_id, NEW.criado_por),
    'INSERT',
    'demandas',
    NEW.id,
    CONCAT('Demanda criada com status ', NEW.status)
  );
END$$

CREATE TRIGGER `trg_demandas_au_log`
AFTER UPDATE ON `demandas`
FOR EACH ROW
BEGIN
  INSERT INTO `logs_sistema` (`usuario_id`, `acao`, `entidade`, `entidade_id`, `descricao`)
  VALUES (
    COALESCE(@app_user_id, NEW.criado_por),
    'UPDATE',
    'demandas',
    NEW.id,
    CONCAT('Demanda atualizada. Status de ', OLD.status, ' para ', NEW.status, '. Última atualização em ', NEW.data_ultima_atualizacao)
  );
END$$

CREATE TRIGGER `trg_demandas_ad_log`
AFTER DELETE ON `demandas`
FOR EACH ROW
BEGIN
  INSERT INTO `logs_sistema` (`usuario_id`, `acao`, `entidade`, `entidade_id`, `descricao`)
  VALUES (
    COALESCE(@app_user_id, OLD.criado_por),
    'DELETE',
    'demandas',
    OLD.id,
    'Demanda removida'
  );
END$$

DELIMITER ;

-- ---------------------------------------------------------
-- Usuário MASTER inicial
-- senha em texto para cadastro inicial: Master@123
-- hash bcrypt correspondente abaixo
-- ---------------------------------------------------------
INSERT INTO `usuarios` (
  `nome_completo`,
  `email`,
  `endereco`,
  `whatsapp`,
  `numero_decreto`,
  `data_nascimento`,
  `usuario`,
  `senha_hash`,
  `perfil`,
  `primeiro_login`,
  `ativo`,
  `ultimo_login`
) VALUES (
  'Administrador Master',
  'master@emendas.local',
  'Endereço administrativo',
  '5500000000000',
  'MASTER-001',
  '1990-01-01',
  'master',
  '$2y$10$2oUApvafnV5fGecK4fX3Z.2UNx9lKk2fuRu8etIRsEhM99f2m2vHW',
  'master',
  0,
  1,
  NOW()
)
ON DUPLICATE KEY UPDATE
  `nome_completo` = VALUES(`nome_completo`),
  `usuario` = VALUES(`usuario`),
  `ativo` = VALUES(`ativo`),
  `primeiro_login` = VALUES(`primeiro_login`);

SET FOREIGN_KEY_CHECKS = 1;

```
#### `sql/schema.sql`
```sql
-- =========================================================
-- Sistema de Gestão de Emendas Governamentais
-- Banco: santo821_emenda
-- Compatível com MySQL 5.7+/8.0 e phpMyAdmin (HostGator)
-- =========================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET SQL_MODE = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- Criação do banco
-- ---------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `santo821_emenda`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `santo821_emenda`;

-- ---------------------------------------------------------
-- Criação do usuário de banco (quando permitido pelo host)
-- Em hospedagem compartilhada pode já existir via painel.
-- ---------------------------------------------------------
CREATE USER IF NOT EXISTS 'santo821_emenda'@'localhost' IDENTIFIED BY 'php@3903.';
GRANT ALL PRIVILEGES ON `santo821_emenda`.* TO 'santo821_emenda'@'localhost';
FLUSH PRIVILEGES;

-- ---------------------------------------------------------
-- Reimportação segura (ordem por dependência)
-- ---------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_demandas_ai_log`;
DROP TRIGGER IF EXISTS `trg_demandas_au_log`;
DROP TRIGGER IF EXISTS `trg_demandas_ad_log`;

DROP TABLE IF EXISTS `redefinicao_senha`;
DROP TABLE IF EXISTS `logs_sistema`;
DROP TABLE IF EXISTS `notificacoes`;
DROP TABLE IF EXISTS `demandas`;
DROP TABLE IF EXISTS `usuarios`;

-- ---------------------------------------------------------
-- 1) Tabela: usuarios
-- ---------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome_completo` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `endereco` VARCHAR(255) NOT NULL,
  `whatsapp` VARCHAR(20) NOT NULL,
  `numero_decreto` VARCHAR(50) NOT NULL,
  `data_nascimento` DATE NOT NULL,
  `usuario` VARCHAR(60) NOT NULL,
  `senha_hash` VARCHAR(255) NOT NULL,
  `perfil` ENUM('master','funcionario') NOT NULL,
  `primeiro_login` TINYINT(1) NOT NULL DEFAULT 1,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_login` DATETIME NULL,
  `session_token` VARCHAR(128) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  UNIQUE KEY `uq_usuarios_usuario` (`usuario`),
  UNIQUE KEY `uq_usuarios_numero_decreto` (`numero_decreto`),
  KEY `idx_usuarios_perfil_ativo` (`perfil`, `ativo`),
  KEY `idx_usuarios_ultimo_login` (`ultimo_login`),
  KEY `idx_usuarios_session_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 2) Tabela: demandas
-- ---------------------------------------------------------
CREATE TABLE `demandas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `emenda` VARCHAR(180) NOT NULL,
  `nome_politico` VARCHAR(150) NOT NULL,
  `tipo_processo` ENUM('prestacao_de_conta','cadastro_de_emenda') NOT NULL,
  `tipo_emenda` ENUM('parlamentar','estadual','municipal') NOT NULL,
  `data_prazo_resposta` DATETIME NOT NULL,
  `data_cadastro_emenda` DATE NOT NULL,
  `observacao` TEXT NULL,
  `anexo_emenda` VARCHAR(255) NULL,
  `anexo_nome_original` VARCHAR(180) NULL,
  `funcionario_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pendente','cadastrado') NOT NULL DEFAULT 'pendente',
  `criado_por` INT UNSIGNED NOT NULL,
  `data_ultima_atualizacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_demandas_status` (`status`),
  KEY `idx_demandas_prazo_resposta` (`data_prazo_resposta`),
  KEY `idx_demandas_funcionario_id` (`funcionario_id`),
  KEY `idx_demandas_criado_por` (`criado_por`),
  KEY `idx_demandas_status_prazo` (`status`, `data_prazo_resposta`),
  CONSTRAINT `fk_demandas_funcionario`
    FOREIGN KEY (`funcionario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_demandas_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 3) Tabela: notificacoes
-- ---------------------------------------------------------
CREATE TABLE `notificacoes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(120) NOT NULL,
  `mensagem` VARCHAR(255) NOT NULL,
  `tipo` VARCHAR(50) NOT NULL,
  `referencia_id` BIGINT UNSIGNED NULL,
  `referencia_tabela` VARCHAR(80) NULL,
  `lida` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notificacoes_usuario_lida` (`usuario_id`, `lida`),
  KEY `idx_notificacoes_tipo` (`tipo`),
  KEY `idx_notificacoes_referencia` (`referencia_tabela`, `referencia_id`),
  KEY `idx_notificacoes_created_at` (`created_at`),
  KEY `idx_notificacoes_automacao` (`usuario_id`, `tipo`, `referencia_id`, `created_at`),
  CONSTRAINT `fk_notificacoes_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 4) Tabela: logs_sistema
-- ---------------------------------------------------------
CREATE TABLE `logs_sistema` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NULL,
  `acao` VARCHAR(100) NOT NULL,
  `entidade` VARCHAR(80) NOT NULL,
  `entidade_id` BIGINT UNSIGNED NULL,
  `descricao` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_usuario_id` (`usuario_id`),
  KEY `idx_logs_entidade` (`entidade`, `entidade_id`),
  KEY `idx_logs_created_at` (`created_at`),
  CONSTRAINT `fk_logs_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 5) Tabela: redefinicao_senha
-- ---------------------------------------------------------
CREATE TABLE `redefinicao_senha` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(128) NOT NULL,
  `expira_em` DATETIME NOT NULL,
  `usado` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_redefinicao_token` (`token`),
  KEY `idx_redefinicao_usuario_id` (`usuario_id`),
  KEY `idx_redefinicao_expira_em` (`expira_em`),
  CONSTRAINT `fk_redefinicao_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Auditoria por trigger (demanda)
-- Uso opcional na aplicação: SET @app_user_id = <id_usuario_logado>;
-- ---------------------------------------------------------
DELIMITER $$

CREATE TRIGGER `trg_demandas_ai_log`
AFTER INSERT ON `demandas`
FOR EACH ROW
BEGIN
  INSERT INTO `logs_sistema` (`usuario_id`, `acao`, `entidade`, `entidade_id`, `descricao`)
  VALUES (
    COALESCE(@app_user_id, NEW.criado_por),
    'INSERT',
    'demandas',
    NEW.id,
    CONCAT('Demanda criada com status ', NEW.status)
  );
END$$

CREATE TRIGGER `trg_demandas_au_log`
AFTER UPDATE ON `demandas`
FOR EACH ROW
BEGIN
  INSERT INTO `logs_sistema` (`usuario_id`, `acao`, `entidade`, `entidade_id`, `descricao`)
  VALUES (
    COALESCE(@app_user_id, NEW.criado_por),
    'UPDATE',
    'demandas',
    NEW.id,
    CONCAT('Demanda atualizada. Status de ', OLD.status, ' para ', NEW.status, '. Última atualização em ', NEW.data_ultima_atualizacao)
  );
END$$

CREATE TRIGGER `trg_demandas_ad_log`
AFTER DELETE ON `demandas`
FOR EACH ROW
BEGIN
  INSERT INTO `logs_sistema` (`usuario_id`, `acao`, `entidade`, `entidade_id`, `descricao`)
  VALUES (
    COALESCE(@app_user_id, OLD.criado_por),
    'DELETE',
    'demandas',
    OLD.id,
    'Demanda removida'
  );
END$$

DELIMITER ;

-- ---------------------------------------------------------
-- Usuário MASTER inicial
-- senha em texto para cadastro inicial: Master@123
-- hash bcrypt correspondente abaixo
-- ---------------------------------------------------------
INSERT INTO `usuarios` (
  `nome_completo`,
  `email`,
  `endereco`,
  `whatsapp`,
  `numero_decreto`,
  `data_nascimento`,
  `usuario`,
  `senha_hash`,
  `perfil`,
  `primeiro_login`,
  `ativo`,
  `ultimo_login`
) VALUES (
  'Administrador Master',
  'master@emendas.local',
  'Endereço administrativo',
  '5500000000000',
  'MASTER-001',
  '1990-01-01',
  'master',
  '$2y$10$2oUApvafnV5fGecK4fX3Z.2UNx9lKk2fuRu8etIRsEhM99f2m2vHW',
  'master',
  0,
  1,
  NOW()
)
ON DUPLICATE KEY UPDATE
  `nome_completo` = VALUES(`nome_completo`),
  `usuario` = VALUES(`usuario`),
  `ativo` = VALUES(`ativo`),
  `primeiro_login` = VALUES(`primeiro_login`);

SET FOREIGN_KEY_CHECKS = 1;

```
#### `database/update_2026_03_demandas_notificacoes.sql`
```sql
-- =====================================================================
-- Migração segura (HostGator/phpMyAdmin) - Gestão de Emendas
-- Banco alvo: santo821_emenda
-- Objetivo:
--   1) Atualizar estrutura de demandas para o novo fluxo
--   2) Restringir status a ('pendente','cadastrado')
--   3) Robustecer tabela notificacoes
--   4) Garantir índices de performance e integridade referencial
-- Compatibilidade: MySQL 5.7+ / 8.0+
-- =====================================================================

USE `santo821_emenda`;

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 1;

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_migracao_emendas_202603 $$
CREATE PROCEDURE sp_migracao_emendas_202603()
BEGIN
    DECLARE v_exists INT DEFAULT 0;
    DECLARE v_sql LONGTEXT;

    -- -----------------------------------------------------------------
    -- 0) GARANTIA DE TABELAS BASE
    -- -----------------------------------------------------------------
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas';

    IF v_exists = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tabela demandas não encontrada.';
    END IF;

    SELECT COUNT(*) INTO v_exists
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'notificacoes';

    IF v_exists = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tabela notificacoes não encontrada.';
    END IF;

    -- -----------------------------------------------------------------
    -- 1) DEMANDAS: NOVOS CAMPOS E AJUSTES
    -- -----------------------------------------------------------------

    -- 1.1 tipo_processo
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'tipo_processo';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN tipo_processo ENUM('prestacao_de_conta','cadastro_de_emenda') NULL AFTER nome_politico;
    END IF;

    -- 1.2 tipo_emenda (normalização para ENUM do novo domínio)
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'tipo_emenda';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN tipo_emenda ENUM('parlamentar','estadual','municipal') NULL AFTER tipo_processo;
    ELSE
        -- Mantém dados existentes e converte tipo
        ALTER TABLE demandas
          MODIFY COLUMN tipo_emenda ENUM('parlamentar','estadual','municipal') NULL;
    END IF;

    -- 1.3 data_prazo_resposta
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'data_prazo_resposta';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN data_prazo_resposta DATETIME NULL AFTER tipo_emenda;
    END IF;

    -- 1.4 data_cadastro_emenda
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'data_cadastro_emenda';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN data_cadastro_emenda DATE NULL AFTER data_prazo_resposta;
    END IF;

    -- 1.5 anexo_emenda
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'anexo_emenda';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN anexo_emenda VARCHAR(255) NULL AFTER observacao;
    END IF;


    -- 1.5.1 anexo_nome_original (nome amigável para download)
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'anexo_nome_original';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN anexo_nome_original VARCHAR(180) NULL AFTER anexo_emenda;
    END IF;

    -- 1.6 data_ultima_atualizacao (conforme requisito: nullable)
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'data_ultima_atualizacao';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN data_ultima_atualizacao DATETIME NULL AFTER criado_por;
    ELSE
        ALTER TABLE demandas
          MODIFY COLUMN data_ultima_atualizacao DATETIME NULL;
    END IF;

    -- -----------------------------------------------------------------
    -- 2) MIGRAÇÃO DE DADOS LEGADOS (SE EXISTIREM COLUNAS ANTIGAS)
    -- -----------------------------------------------------------------

    -- 2.1 Preenche tipo_processo padrão
    UPDATE demandas
       SET tipo_processo = COALESCE(tipo_processo, 'cadastro_de_emenda')
     WHERE tipo_processo IS NULL;

    -- 2.2 Normaliza tipo_emenda para domínio válido
    UPDATE demandas
       SET tipo_emenda = 'parlamentar'
     WHERE tipo_emenda IS NULL
        OR tipo_emenda NOT IN ('parlamentar','estadual','municipal');

    -- 2.3 Usa colunas antigas quando disponíveis (prazo_entrega/data_emenda)
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'prazo_entrega';

    IF v_exists > 0 THEN
        UPDATE demandas
           SET data_prazo_resposta = COALESCE(data_prazo_resposta, prazo_entrega)
         WHERE data_prazo_resposta IS NULL;
    END IF;

    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'data_emenda';

    IF v_exists > 0 THEN
        UPDATE demandas
           SET data_cadastro_emenda = COALESCE(data_cadastro_emenda, data_emenda)
         WHERE data_cadastro_emenda IS NULL;
    END IF;

    -- 2.4 Fallback final para evitar null antes de NOT NULL
    UPDATE demandas
       SET data_prazo_resposta = COALESCE(data_prazo_resposta, NOW())
     WHERE data_prazo_resposta IS NULL;

    UPDATE demandas
       SET data_cadastro_emenda = COALESCE(data_cadastro_emenda, CURDATE())
     WHERE data_cadastro_emenda IS NULL;

    UPDATE demandas
       SET data_ultima_atualizacao = COALESCE(data_ultima_atualizacao, updated_at, NOW())
     WHERE data_ultima_atualizacao IS NULL;

    -- 2.5 Enforça NOT NULL nos campos mandatórios do novo fluxo
    ALTER TABLE demandas
      MODIFY COLUMN tipo_processo ENUM('prestacao_de_conta','cadastro_de_emenda') NOT NULL,
      MODIFY COLUMN tipo_emenda ENUM('parlamentar','estadual','municipal') NOT NULL,
      MODIFY COLUMN data_prazo_resposta DATETIME NOT NULL,
      MODIFY COLUMN data_cadastro_emenda DATE NOT NULL;

    -- -----------------------------------------------------------------
    -- 3) STATUS: RESTRIÇÃO PARA ('pendente','cadastrado')
    -- -----------------------------------------------------------------

    -- Mapeia status legado para domínio novo
    UPDATE demandas
       SET status = CASE
           WHEN status IN ('cadastrado') THEN 'cadastrado'
           WHEN status IN ('em_andamento','concluida') THEN 'cadastrado'
           WHEN status IN ('atrasada') THEN 'pendente'
           ELSE 'pendente'
       END;

    ALTER TABLE demandas
      MODIFY COLUMN status ENUM('pendente','cadastrado') NOT NULL DEFAULT 'pendente';

    -- -----------------------------------------------------------------
    -- 4) REMOÇÃO DE COLUNAS LEGADAS (SE EXISTIREM)
    -- -----------------------------------------------------------------

    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'prazo_entrega';
    IF v_exists > 0 THEN
      ALTER TABLE demandas DROP COLUMN prazo_entrega;
    END IF;

    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'data_emenda';
    IF v_exists > 0 THEN
      ALTER TABLE demandas DROP COLUMN data_emenda;
    END IF;

    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'data_conclusao';
    IF v_exists > 0 THEN
      ALTER TABLE demandas DROP COLUMN data_conclusao;
    END IF;

    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'observacao_conclusao';
    IF v_exists > 0 THEN
      ALTER TABLE demandas DROP COLUMN observacao_conclusao;
    END IF;

    -- -----------------------------------------------------------------
    -- 5) NOTIFICACOES: AJUSTE DE ESTRUTURA E TIPAGEM
    -- -----------------------------------------------------------------

    -- Garante colunas principais
    SELECT COUNT(*) INTO v_exists FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'usuario_id';
    IF v_exists = 0 THEN
        ALTER TABLE notificacoes ADD COLUMN usuario_id INT UNSIGNED NULL AFTER id;
    END IF;

    SELECT COUNT(*) INTO v_exists FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'titulo';
    IF v_exists = 0 THEN
        ALTER TABLE notificacoes ADD COLUMN titulo VARCHAR(120) NULL AFTER usuario_id;
    END IF;

    SELECT COUNT(*) INTO v_exists FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'mensagem';
    IF v_exists = 0 THEN
        ALTER TABLE notificacoes ADD COLUMN mensagem VARCHAR(255) NULL AFTER titulo;
    END IF;

    SELECT COUNT(*) INTO v_exists FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'tipo';
    IF v_exists = 0 THEN
        ALTER TABLE notificacoes ADD COLUMN tipo VARCHAR(50) NULL AFTER mensagem;
    END IF;

    SELECT COUNT(*) INTO v_exists FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'referencia_id';
    IF v_exists = 0 THEN
        ALTER TABLE notificacoes ADD COLUMN referencia_id BIGINT UNSIGNED NULL AFTER tipo;
    END IF;

    SELECT COUNT(*) INTO v_exists FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'referencia_tabela';
    IF v_exists = 0 THEN
        ALTER TABLE notificacoes ADD COLUMN referencia_tabela VARCHAR(80) NULL AFTER referencia_id;
    END IF;

    SELECT COUNT(*) INTO v_exists FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'lida';
    IF v_exists = 0 THEN
        ALTER TABLE notificacoes ADD COLUMN lida TINYINT(1) NOT NULL DEFAULT 0 AFTER referencia_tabela;
    END IF;

    SELECT COUNT(*) INTO v_exists FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'created_at';
    IF v_exists = 0 THEN
        ALTER TABLE notificacoes ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER lida;
    END IF;

    -- Normaliza nulidade/tipos para modelo final
    UPDATE notificacoes SET titulo = COALESCE(titulo, 'Notificação') WHERE titulo IS NULL;
    UPDATE notificacoes SET mensagem = COALESCE(mensagem, 'Sem mensagem') WHERE mensagem IS NULL;
    UPDATE notificacoes SET tipo = COALESCE(tipo, 'geral') WHERE tipo IS NULL;

    ALTER TABLE notificacoes
      MODIFY COLUMN usuario_id INT UNSIGNED NOT NULL,
      MODIFY COLUMN titulo VARCHAR(120) NOT NULL,
      MODIFY COLUMN mensagem VARCHAR(255) NOT NULL,
      MODIFY COLUMN tipo VARCHAR(50) NOT NULL,
      MODIFY COLUMN referencia_id BIGINT UNSIGNED NULL,
      MODIFY COLUMN referencia_tabela VARCHAR(80) NULL,
      MODIFY COLUMN lida TINYINT(1) NOT NULL DEFAULT 0,
      MODIFY COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

    -- -----------------------------------------------------------------
    -- 6) ÍNDICES (com proteção contra duplicidade)
    -- -----------------------------------------------------------------

    -- demandas(funcionario_id)
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND INDEX_NAME = 'idx_demandas_funcionario_id';
    IF v_exists = 0 THEN
      CREATE INDEX idx_demandas_funcionario_id ON demandas (funcionario_id);
    END IF;

    -- demandas(status)
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND INDEX_NAME = 'idx_demandas_status';
    IF v_exists = 0 THEN
      CREATE INDEX idx_demandas_status ON demandas (status);
    END IF;

    -- demandas(data_prazo_resposta)
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND INDEX_NAME = 'idx_demandas_prazo_resposta';
    IF v_exists = 0 THEN
      CREATE INDEX idx_demandas_prazo_resposta ON demandas (data_prazo_resposta);
    END IF;

    -- índice composto sugerido para filtros do painel
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND INDEX_NAME = 'idx_demandas_func_status_prazo';
    IF v_exists = 0 THEN
      CREATE INDEX idx_demandas_func_status_prazo ON demandas (funcionario_id, status, data_prazo_resposta);
    END IF;

    -- notificacoes(created_at)
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'notificacoes'
       AND INDEX_NAME = 'idx_notificacoes_created_at';
    IF v_exists = 0 THEN
      CREATE INDEX idx_notificacoes_created_at ON notificacoes (created_at);
    END IF;

    -- notificacoes(usuario_id,lida)
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'notificacoes'
       AND INDEX_NAME = 'idx_notificacoes_usuario_lida';
    IF v_exists = 0 THEN
      CREATE INDEX idx_notificacoes_usuario_lida ON notificacoes (usuario_id, lida);
    END IF;

    -- -----------------------------------------------------------------
    -- 7) INTEGRIDADE REFERENCIAL (FK notificacoes.usuario_id -> usuarios.id)
    -- -----------------------------------------------------------------

    SELECT COUNT(*) INTO v_exists
      FROM information_schema.REFERENTIAL_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND CONSTRAINT_NAME = 'fk_notificacoes_usuario';

    IF v_exists = 0 THEN
      ALTER TABLE notificacoes
        ADD CONSTRAINT fk_notificacoes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE;
    END IF;

END $$

CALL sp_migracao_emendas_202603() $$
DROP PROCEDURE IF EXISTS sp_migracao_emendas_202603 $$

DELIMITER ;

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- =====================================================================
-- Fim da migração
-- =====================================================================

```
### Explicação técnica breve
Implementação compatível com as etapas anteriores, preservando autenticação, CSRF, regras de perfil e consistência entre banco/model/controller/view.
### Validação de compatibilidade
- Sintaxe PHP validada e integração mantida com rotas e views existentes.

## 2. Atualizar models
### Arquivos
- `app/Models/Demand.php`
### Conteúdo completo dos arquivos
#### `app/Models/Demand.php`
```php
<?php

namespace App\Models;

use App\Core\Database;

class Demand
{
    public const STATUS_ALLOWED = ['pendente', 'cadastrado'];
    public const PROCESS_TYPES = ['prestacao_de_conta', 'cadastro_de_emenda'];
    public const AMENDMENT_TYPES = ['parlamentar', 'estadual', 'municipal'];

    public function countFiltered(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = 'SELECT COUNT(*) AS total FROM demandas d ' . $where;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function paginatedFiltered(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = 'SELECT d.*, u.nome_completo AS funcionario_nome
                FROM demandas d
                JOIN usuarios u ON u.id = d.funcionario_id
                ' . $where . '
                ORDER BY d.data_prazo_resposta ASC
                LIMIT :limit OFFSET :offset';

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO demandas (emenda, nome_politico, tipo_processo, tipo_emenda, data_prazo_resposta, data_cadastro_emenda,
                    observacao, anexo_emenda, anexo_nome_original, funcionario_id, status, criado_por, data_ultima_atualizacao)
                VALUES (:emenda, :nome_politico, :tipo_processo, :tipo_emenda, :data_prazo_resposta, :data_cadastro_emenda,
                    :observacao, :anexo_emenda, :anexo_nome_original, :funcionario_id, :status, :criado_por, NOW())';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);
        return (int)Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE demandas
                SET emenda = :emenda,
                    nome_politico = :nome_politico,
                    tipo_processo = :tipo_processo,
                    tipo_emenda = :tipo_emenda,
                    data_prazo_resposta = :data_prazo_resposta,
                    data_cadastro_emenda = :data_cadastro_emenda,
                    observacao = :observacao,
                    anexo_emenda = :anexo_emenda,
                    anexo_nome_original = :anexo_nome_original,
                    funcionario_id = :funcionario_id,
                    status = :status,
                    data_ultima_atualizacao = NOW()
                WHERE id = :id';
        return Database::connection()->prepare($sql)->execute(['id' => $id] + $data);
    }

    public function delete(int $id): bool
    {
        return Database::connection()->prepare('DELETE FROM demandas WHERE id = :id')->execute(['id' => $id]);
    }

    public function byEmployee(int $employeeId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM demandas WHERE funcionario_id = :id ORDER BY data_prazo_resposta ASC');
        $stmt->execute(['id' => $employeeId]);
        return $stmt->fetchAll();
    }

    public function findOwnedById(int $id, int $employeeId): ?array
    {
        $sql = 'SELECT * FROM demandas WHERE id = :id AND funcionario_id = :funcionario_id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'funcionario_id' => $employeeId,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM demandas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function updateStatusByEmployee(int $id, int $employeeId, string $status): bool
    {
        $sql = 'UPDATE demandas
                SET status = :status, data_ultima_atualizacao = NOW(), updated_at = NOW()
                WHERE id = :id AND funcionario_id = :funcionario_id';

        return Database::connection()->prepare($sql)->execute([
            'id' => $id,
            'funcionario_id' => $employeeId,
            'status' => $status,
        ]);
    }

    public function dueIn24Hours(): array
    {
        $sql = 'SELECT d.*, u.nome_completo AS funcionario_nome
                FROM demandas d
                JOIN usuarios u ON u.id = d.funcionario_id
                WHERE d.data_prazo_resposta BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
                ORDER BY d.data_prazo_resposta ASC';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function upcomingDeadlines(int $limit = 8): array
    {
        $sql = 'SELECT d.id, d.emenda, d.data_prazo_resposta, d.status, u.nome_completo AS funcionario_nome
                FROM demandas d
                JOIN usuarios u ON u.id = d.funcionario_id
                ORDER BY d.data_prazo_resposta ASC
                LIMIT :limit';
        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function stats(?int $employeeId = null): array
    {
        $where = $employeeId ? ' WHERE funcionario_id = :employee_id' : '';
        $stmt = Database::connection()->prepare('SELECT status, COUNT(*) total FROM demandas' . $where . ' GROUP BY status');
        $params = $employeeId ? ['employee_id' => $employeeId] : [];
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $stats = ['total' => 0, 'pendente' => 0, 'cadastrado' => 0];
        foreach ($rows as $row) {
            $stats['total'] += (int)$row['total'];
            if (isset($stats[$row['status']])) {
                $stats[$row['status']] = (int)$row['total'];
            }
        }
        return $stats;
    }

    public function employeeSummary(int $employeeId): array
    {
        return $this->stats($employeeId);
    }

    public function recentlyUpdatedForEmployee(int $employeeId, string $since): array
    {
        $sql = 'SELECT * FROM demandas WHERE funcionario_id = :employee_id AND updated_at > :since ORDER BY updated_at DESC LIMIT 30';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId, 'since' => $since]);
        return $stmt->fetchAll();
    }

    private function buildFilters(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUS_ALLOWED, true)) {
            $clauses[] = 'd.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['funcionario_id']) && ctype_digit((string)$filters['funcionario_id'])) {
            $clauses[] = 'd.funcionario_id = :funcionario_id';
            $params['funcionario_id'] = (int)$filters['funcionario_id'];
        }

        if (!empty($filters['prazo_de'])) {
            $clauses[] = 'd.data_prazo_resposta >= :prazo_de';
            $params['prazo_de'] = $filters['prazo_de'] . ' 00:00:00';
        }

        if (!empty($filters['prazo_ate'])) {
            $clauses[] = 'd.data_prazo_resposta <= :prazo_ate';
            $params['prazo_ate'] = $filters['prazo_ate'] . ' 23:59:59';
        }

        $where = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';

        return [$where, $params];
    }
}

```
### Explicação técnica breve
Implementação compatível com as etapas anteriores, preservando autenticação, CSRF, regras de perfil e consistência entre banco/model/controller/view.
### Validação de compatibilidade
- Sintaxe PHP validada e integração mantida com rotas e views existentes.

## 3. Atualizar controllers
### Arquivos
- `app/Controllers/MasterController.php`
- `app/Controllers/AttachmentController.php`
### Conteúdo completo dos arquivos
#### `app/Controllers/MasterController.php`
```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\NotificationDeadlineService;
use App\Services\SecureAttachmentService;

class MasterController extends Controller
{
    public function __construct(
        private readonly User $users = new User(),
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly NotificationDeadlineService $deadlineService = new NotificationDeadlineService(),
        private readonly SecureAttachmentService $attachmentService = new SecureAttachmentService()
    ) {
    }

    public function dashboard(): void
    {
        $this->requireAuth('master');
        $masterId = (int)$_SESSION['user']['id'];
        $this->deadlineService->runAutomationForMaster($masterId);

        $this->view('master/dashboard/index', [
            'stats' => $this->demands->stats(),
            'demands' => $this->demands->paginatedFiltered([], 300, 0),
            'notifications' => $this->notifications->forUser($masterId, 8),
            'upcoming' => $this->demands->upcomingDeadlines(8),
        ]);
    }

    public function employees(): void { /* unchanged */
        $this->requireAuth('master');
        $search = trim((string)($_GET['search'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 8;
        $offset = ($page - 1) * $perPage;
        $total = $this->users->countEmployees($search !== '' ? $search : null);
        $employees = $this->users->paginatedEmployees($perPage, $offset, $search !== '' ? $search : null);
        $this->view('master/employees/index', ['employees' => $employees,'search' => $search,'page' => $page,'totalPages' => max(1, (int)ceil($total / $perPage))]);
    }

    public function createEmployee(): void { /* unchanged */
        $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$payload = $this->validateEmployeePayload();if (!$payload['ok']) {flash('error', implode(' ', $payload['errors']));redirect('master/employees');}
        $data = $payload['data'];if ($this->users->emailExists($data['email'])) {flash('error', 'E-mail já cadastrado.');redirect('master/employees');}
        if ($this->users->decreeExists($data['numero_decreto'])) {flash('error', 'Número de decreto já cadastrado.');redirect('master/employees');}
        $this->users->createEmployee(['nome_completo'=>$data['nome_completo'],'email'=>$data['email'],'endereco'=>$data['endereco'],'whatsapp'=>$data['whatsapp'],'numero_decreto'=>$data['numero_decreto'],'data_nascimento'=>$data['data_nascimento'],'usuario'=>$data['numero_decreto'],'senha_hash'=>password_hash(normalize_birth_password($data['data_nascimento']), PASSWORD_DEFAULT)]);
        $this->logAction('create', 'usuarios', null, 'Cadastro de funcionário realizado pelo Master');flash('success', 'Funcionário cadastrado com sucesso.');redirect('master/employees');
    }
    public function updateEmployee(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}$payload=$this->validateEmployeePayload();if(!$payload['ok']){flash('error',implode(' ',$payload['errors']));redirect('master/employees');}$data=$payload['data'];if($this->users->emailExists($data['email'],$id)||$this->users->decreeExists($data['numero_decreto'],$id)){flash('error','E-mail ou decreto já em uso.');redirect('master/employees');}$this->users->updateEmployee($id,$data);$this->logAction('update','usuarios',$id,'Dados do funcionário atualizados pelo Master');flash('success','Funcionário atualizado com sucesso.');redirect('master/employees'); }
    public function changeEmployeePassword(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);$newPassword=(string)($_POST['new_password']??'');if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}if(strlen($newPassword)<8){flash('error','Informe uma nova senha com no mínimo 8 caracteres.');redirect('master/employees');}$this->users->updatePassword($id,password_hash($newPassword,PASSWORD_DEFAULT),true);$this->logAction('update_password','usuarios',$id,'Master alterou senha de funcionário e reativou primeiro login');flash('success','Senha do funcionário alterada com sucesso.');redirect('master/employees'); }
    public function toggleEmployee(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}$active=(int)($_POST['active']??0)===1;$this->users->toggleEmployeeStatus($id,$active);$this->logAction('toggle','usuarios',$id,$active?'Funcionário ativado':'Funcionário desativado');flash('success',$active?'Funcionário ativado.':'Funcionário desativado.');redirect('master/employees'); }
    public function deleteEmployee(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}$this->users->toggleEmployeeStatus($id,false);$this->logAction('soft_delete','usuarios',$id,'Funcionário desativado por ação de exclusão lógica');flash('success','Funcionário desativado com sucesso.');redirect('master/employees'); }

    public function demands(): void
    {
        $this->requireAuth('master');
        $this->deadlineService->runAutomationForMaster((int)$_SESSION['user']['id']);
        $filters = ['status'=>trim((string)($_GET['status'] ?? '')),'funcionario_id'=>trim((string)($_GET['funcionario_id'] ?? '')),'prazo_de'=>trim((string)($_GET['prazo_de'] ?? '')),'prazo_ate'=>trim((string)($_GET['prazo_ate'] ?? ''))];
        $page = max(1, (int)($_GET['page'] ?? 1));$perPage = 10;$offset = ($page - 1) * $perPage;$total = $this->demands->countFiltered($filters);
        $this->view('master/demands/index', ['demands'=>$this->demands->paginatedFiltered($filters,$perPage,$offset),'employees'=>$this->users->allActiveEmployees(),'filters'=>$filters,'page'=>$page,'totalPages'=>max(1,(int)ceil($total/$perPage))]);
    }

    public function createDemand(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/demands');

        $payload = $this->validateDemandPayload();
        if (!$payload['ok']) { flash('error', implode(' ', $payload['errors'])); redirect('master/demands'); }

        $dataToPersist = $payload['data'];
        $uploadOriginalName = $dataToPersist['_upload_original_name'] ?? null;
        unset($dataToPersist['_upload_original_name']);

        $demandId = $this->demands->create($dataToPersist + ['criado_por' => (int)$_SESSION['user']['id']]);
        $demand = $this->demands->findById($demandId) ?? ($payload['data'] + ['id' => $demandId]);

        $masterId = (int)$_SESSION['user']['id'];
        $this->deadlineService->notifyDemandEventToMaster($masterId, $demand, 'Nova demanda cadastrada', 'A demanda foi cadastrada com sucesso no sistema.', 'demanda_criada_master');
        $this->deadlineService->notifyDemandAssignedToEmployee((int)$payload['data']['funcionario_id'], $demand);

        $this->logAction('create', 'demandas', $demandId, 'Demanda cadastrada pelo Master');
        if (is_string($uploadOriginalName) && $uploadOriginalName !== '') {
            $this->logAction('upload_anexo', 'demandas', $demandId, 'Upload do anexo: ' . $uploadOriginalName);
        }
        flash('success', 'Demanda cadastrada com sucesso.');
        redirect('master/demands');
    }

    public function updateDemand(): void
    {
        $this->requireAuth('master');$this->assertCsrfOrRedirect('master/demands');
        $id = (int)($_POST['id'] ?? 0); if ($id <= 0) { flash('error', 'Demanda inválida.'); redirect('master/demands'); }
        $current = $this->demands->findById($id); if (!$current) { flash('error', 'Demanda não encontrada.'); redirect('master/demands'); }
        $payload = $this->validateDemandPayload($current['anexo_emenda'] ?? null);
        if (!$payload['ok']) { flash('error', implode(' ', $payload['errors'])); redirect('master/demands'); }
        $dataToPersist = $payload['data'];
        $uploadOriginalName = $dataToPersist['_upload_original_name'] ?? null;
        unset($dataToPersist['_upload_original_name']);

        $this->demands->update($id, $dataToPersist);
        $updated = $this->demands->findById($id) ?? ($payload['data'] + ['id' => $id]);
        $this->deadlineService->notifyDemandEventToMaster((int)$_SESSION['user']['id'], $updated, 'Demanda atualizada', 'Uma demanda foi atualizada no painel Master.', 'demanda_atualizada_master');
        $this->deadlineService->notifyDemandUpdatedToEmployee((int)$payload['data']['funcionario_id'], $updated);
        $this->logAction('update', 'demandas', $id, 'Demanda atualizada pelo Master');
        if (is_string($uploadOriginalName) && $uploadOriginalName !== '') {
            $this->logAction('upload_anexo', 'demandas', $id, 'Substituição de anexo: ' . $uploadOriginalName);
        }
        flash('success', 'Demanda atualizada com sucesso.');
        redirect('master/demands');
    }

    public function deleteDemand(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/demands');$id=(int)($_POST['id']??0);if($id<=0){flash('error','Demanda inválida.');redirect('master/demands');}$this->demands->delete($id);$this->logAction('delete','demandas',$id,'Demanda removida pelo Master');flash('success','Demanda removida.');redirect('master/demands'); }
    public function readNotification(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/notifications');$notificationId=(int)($_POST['id']??0);$this->notifications->markRead($notificationId,(int)$_SESSION['user']['id']);flash('success','Notificação marcada como lida.');redirect('master/notifications'); }
    public function notifications(): void { $this->requireAuth('master');$masterId=(int)$_SESSION['user']['id'];$this->deadlineService->runAutomationForMaster($masterId);$this->view('master/notifications/index',['notifications'=>$this->notifications->forUser($masterId,50)]); }

    public function polling(): void
    {
        $this->requireAuth('master');
        $this->deadlineService->runAutomationForMaster((int)$_SESSION['user']['id']);
        $this->jsonPollingResponse();
    }

    private function validateEmployeePayload(): array
    {
        $data=['nome_completo'=>trim((string)($_POST['nome_completo']??'')),'email'=>trim((string)($_POST['email']??'')),'endereco'=>trim((string)($_POST['endereco']??'')),'whatsapp'=>trim((string)($_POST['whatsapp']??'')),'numero_decreto'=>trim((string)($_POST['numero_decreto']??'')),'data_nascimento'=>trim((string)($_POST['data_nascimento']??''))];
        $errors=validate_required($data,['nome_completo','email','endereco','whatsapp','numero_decreto','data_nascimento']);
        if($data['email']!==''&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL)){$errors[]='E-mail inválido.';}
        if($data['data_nascimento']!==''&&strtotime($data['data_nascimento'])===false){$errors[]='Data de nascimento inválida.';}
        return ['ok'=>$errors===[],'data'=>$data,'errors'=>$errors];
    }

    private function validateDemandPayload(?string $existingAttachment = null): array
    {
        $data = [
            'emenda' => trim((string)($_POST['emenda'] ?? '')),
            'nome_politico' => trim((string)($_POST['nome_politico'] ?? '')),
            'tipo_processo' => trim((string)($_POST['tipo_processo'] ?? '')),
            'tipo_emenda' => trim((string)($_POST['tipo_emenda'] ?? '')),
            'data_prazo_resposta' => trim((string)($_POST['data_prazo_resposta'] ?? '')),
            'data_cadastro_emenda' => trim((string)($_POST['data_cadastro_emenda'] ?? '')),
            'observacao' => trim((string)($_POST['observacao'] ?? '')),
            'funcionario_id' => (int)($_POST['funcionario_id'] ?? 0),
            'status' => trim((string)($_POST['status'] ?? 'pendente')),
            'anexo_emenda' => $existingAttachment,
            'anexo_nome_original' => trim((string)($_POST['anexo_nome_original'] ?? '')),
        ];

        $errors = validate_required($data, ['emenda', 'nome_politico', 'tipo_processo', 'tipo_emenda', 'data_prazo_resposta', 'data_cadastro_emenda']);
        if (!in_array($data['tipo_processo'], Demand::PROCESS_TYPES, true)) { $errors[] = 'Tipo de processo inválido.'; }
        if (!in_array($data['tipo_emenda'], Demand::AMENDMENT_TYPES, true)) { $errors[] = 'Tipo de emenda inválido.'; }
        if (!in_array($data['status'], Demand::STATUS_ALLOWED, true)) { $errors[] = 'Status da demanda inválido.'; }
        if ($data['funcionario_id'] <= 0 || !$this->users->existsEmployeeById($data['funcionario_id'])) { $errors[] = 'Selecione um funcionário válido para a demanda.'; }

        $prazoTimestamp = strtotime($data['data_prazo_resposta']);
        $cadastroTimestamp = strtotime($data['data_cadastro_emenda']);
        if ($prazoTimestamp === false) { $errors[] = 'Data prazo de resposta inválida.'; } else { $data['data_prazo_resposta'] = date('Y-m-d H:i:s', $prazoTimestamp); }
        if ($cadastroTimestamp === false) { $errors[] = 'Data de cadastro da emenda inválida.'; } else { $data['data_cadastro_emenda'] = date('Y-m-d', $cadastroTimestamp); }

        $upload = $this->attachmentService->store($_FILES['anexo_emenda'] ?? null);
        if (!$upload['ok']) {
            $errors = array_merge($errors, $upload['errors']);
        } elseif ($upload['path'] !== null) {
            $data['anexo_emenda'] = $upload['path'];
            $data['_upload_original_name'] = $upload['original_name'];
            $data['anexo_nome_original'] = (string)$upload['original_name'];
        }

        return ['ok' => $errors === [], 'data' => $data, 'errors' => $errors];
    }

    private function jsonPollingResponse(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $userId = (int)$_SESSION['user']['id'];
        $afterId = max(0, (int)($_GET['after_id'] ?? 0));
        $items = $this->notifications->latestForUser($userId, $afterId, 20);
        echo json_encode(['ok' => true,'unread_count' => $this->notifications->unreadCount($userId),'items' => $items,'latest_id' => $this->notifications->latestIdForUser($userId)]);
        exit;
    }

    private function assertCsrfOrRedirect(string $redirectPath): void
    {
        if (!verify_csrf($_POST['_csrf'] ?? null)) { flash('error', 'Falha de segurança na requisição.'); redirect($redirectPath); }
    }

    private function logAction(string $acao, string $entidade, ?int $entidadeId, string $descricao): void
    {
        $this->logs->create(['usuario_id'=>(int)($_SESSION['user']['id'] ?? 0) ?: null,'acao'=>$acao,'entidade'=>$entidade,'entidade_id'=>$entidadeId,'descricao'=>$descricao,'ip'=>client_ip(),'user_agent'=>$_SERVER['HTTP_USER_AGENT'] ?? null]);
    }
}

```
#### `app/Controllers/AttachmentController.php`
```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\SystemLog;
use App\Services\SecureAttachmentService;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly SecureAttachmentService $attachments = new SecureAttachmentService()
    ) {
    }

    public function downloadDemandAttachment(): void
    {
        $this->requireAuth();

        $demandId = (int)($_GET['demand_id'] ?? 0);
        if ($demandId <= 0) {
            http_response_code(400);
            echo 'Anexo inválido.';
            return;
        }

        $demand = $this->demands->findById($demandId);
        if (!$demand || empty($demand['anexo_emenda'])) {
            http_response_code(404);
            echo 'Anexo não encontrado.';
            return;
        }

        $role = (string)($_SESSION['user']['role'] ?? '');
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($role !== 'master' && (int)$demand['funcionario_id'] !== $userId) {
            http_response_code(403);
            echo 'Acesso negado ao anexo.';
            return;
        }

        $relativePath = (string)$demand['anexo_emenda'];
        $file = $this->attachments->resolveAbsolutePath($relativePath);
        if ($file === null) {
            http_response_code(404);
            echo 'Arquivo indisponível.';
            return;
        }

        $originalName = (string)($demand['anexo_nome_original'] ?? '');
        if ($originalName === '') {
            $originalName = $this->attachments->originalName($relativePath);
        }
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $inline = (($_GET['inline'] ?? '0') === '1') && $extension === 'pdf';

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($file);

        $this->logs->create([
            'usuario_id' => $userId,
            'acao' => 'download_anexo',
            'entidade' => 'demandas',
            'entidade_id' => $demandId,
            'descricao' => 'Download/visualização de anexo da demanda',
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . (string)filesize($file));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes($originalName) . '"');

        readfile($file);
        exit;
    }
}

```
### Explicação técnica breve
Implementação compatível com as etapas anteriores, preservando autenticação, CSRF, regras de perfil e consistência entre banco/model/controller/view.
### Validação de compatibilidade
- Sintaxe PHP validada e integração mantida com rotas e views existentes.

## 4. Atualizar views do Master
### Arquivos
- `app/views/master/demands/index.php`
### Conteúdo completo dos arquivos
#### `app/views/master/demands/index.php`
```php
<?php
$statusLabels = ['pendente' => 'Pendente', 'cadastrado' => 'Cadastrado'];
$processLabels = ['prestacao_de_conta' => 'Prestação de conta', 'cadastro_de_emenda' => 'Cadastro de emenda'];
$amendmentLabels = ['parlamentar' => 'Parlamentar', 'estadual' => 'Estadual', 'municipal' => 'Municipal'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Gestão de Demandas</h3>
  <span class="text-muted">Fluxo atualizado com anexos e notificação automática</span>
</div>

<div class="card card-ui mb-3"><div class="card-body">
<form method="get" action="<?= url('master/demands') ?>" class="row g-2">
  <div class="col-md-3"><select class="form-select" name="status"><option value="">Todos os status</option><?php foreach ($statusLabels as $k=>$l): ?><option value="<?= $k ?>" <?= ($filters['status']??'')===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="funcionario_id"><option value="">Todos os funcionários</option><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>" <?= (string)($filters['funcionario_id']??'')===(string)$employee['id']?'selected':'' ?>><?= e($employee['nome_completo']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input type="date" class="form-control" name="prazo_de" value="<?= e($filters['prazo_de'] ?? '') ?>"></div>
  <div class="col-md-2"><input type="date" class="form-control" name="prazo_ate" value="<?= e($filters['prazo_ate'] ?? '') ?>"></div>
  <div class="col-md-2 d-grid"><button class="btn btn-dark">Filtrar</button></div>
</form>
</div></div>

<div class="card card-ui mb-4"><div class="card-body">
<h5 class="mb-3">Cadastrar demanda</h5>
<form method="post" action="<?= url('master/demands/create') ?>" class="row g-2" enctype="multipart/form-data">
  <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
  <div class="col-md-4"><input class="form-control" name="emenda" placeholder="Nome da emenda" required></div>
  <div class="col-md-4"><input class="form-control" name="nome_politico" placeholder="Nome do político" required></div>
  <div class="col-md-4"><select class="form-select" name="tipo_processo" required><option value="">Tipo de processo</option><?php foreach ($processLabels as $k=>$l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="tipo_emenda" required><option value="">Tipo de emenda</option><?php foreach ($amendmentLabels as $k=>$l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><input type="datetime-local" class="form-control" name="data_prazo_resposta" required></div>
  <div class="col-md-3"><input type="date" class="form-control" name="data_cadastro_emenda" required></div>
  <div class="col-md-3"><select class="form-select" name="status" required><?php foreach ($statusLabels as $k=>$l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-8"><select class="form-select" name="funcionario_id" required><option value="">Selecione o funcionário</option><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>"><?= e($employee['nome_completo']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><input type="file" class="form-control" name="anexo_emenda" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></div>
  <div class="col-12"><textarea class="form-control" name="observacao" rows="2" placeholder="Observações"></textarea></div>
  <div class="col-12 d-grid"><button class="btn btn-primary">Cadastrar demanda</button></div>
</form>
</div></div>

<div class="table-responsive">
<table class="table table-hover table-modern align-middle data-table">
<thead><tr><th>Demanda</th><th>Responsável</th><th>Prazo</th><th>Status</th><th>Anexo</th><th>Ações</th></tr></thead>
<tbody id="masterDemandsBody">
<?php foreach ($demands as $demand): ?>
<tr>
  <td><strong><?= e($demand['emenda']) ?></strong><br><small class="text-muted"><?= e($processLabels[$demand['tipo_processo']] ?? $demand['tipo_processo']) ?> • <?= e($amendmentLabels[$demand['tipo_emenda']] ?? $demand['tipo_emenda']) ?></small></td>
  <td><?= e($demand['funcionario_nome']) ?></td>
  <td><?= e(date('d/m/Y H:i', strtotime($demand['data_prazo_resposta']))) ?></td>
  <td><span class="badge bg-secondary"><?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></span></td>
  <td><?php if (!empty($demand['anexo_emenda'])): ?><a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= url('anexos/demandas/download?demand_id=' . (int)$demand['id']) ?>">Visualizar</a><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
  <td>
    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editar<?= (int)$demand['id'] ?>">Editar</button>
    <form method="post" action="<?= url('master/demands/delete') ?>" class="d-inline" onsubmit="return confirm('Deseja remover esta demanda?');"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$demand['id'] ?>"><input type="hidden" name="anexo_nome_original" value="<?= e($demand['anexo_nome_original'] ?? '') ?>"><button class="btn btn-sm btn-danger">Excluir</button></form>
  </td>
</tr>
<div class="modal fade" id="editar<?= (int)$demand['id'] ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Editar demanda</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="post" action="<?= url('master/demands/update') ?>" enctype="multipart/form-data"><div class="modal-body row g-2">
<input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$demand['id'] ?>"><input type="hidden" name="anexo_nome_original" value="<?= e($demand['anexo_nome_original'] ?? '') ?>">
<div class="col-md-6"><input class="form-control" name="emenda" value="<?= e($demand['emenda']) ?>" required></div>
<div class="col-md-6"><input class="form-control" name="nome_politico" value="<?= e($demand['nome_politico']) ?>" required></div>
<div class="col-md-4"><select class="form-select" name="tipo_processo" required><?php foreach ($processLabels as $k=>$l): ?><option value="<?= $k ?>" <?= $demand['tipo_processo']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><select class="form-select" name="tipo_emenda" required><?php foreach ($amendmentLabels as $k=>$l): ?><option value="<?= $k ?>" <?= $demand['tipo_emenda']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><select class="form-select" name="status" required><?php foreach ($statusLabels as $k=>$l): ?><option value="<?= $k ?>" <?= $demand['status']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><input class="form-control" type="datetime-local" name="data_prazo_resposta" value="<?= e(date('Y-m-d\TH:i', strtotime($demand['data_prazo_resposta']))) ?>" required></div>
<div class="col-md-4"><input class="form-control" type="date" name="data_cadastro_emenda" value="<?= e($demand['data_cadastro_emenda']) ?>" required></div>
<div class="col-md-4"><input type="file" class="form-control" name="anexo_emenda" accept=".pdf,.docx"></div>
<div class="col-md-12"><select class="form-select" name="funcionario_id" required><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>" <?= (int)$employee['id']===(int)$demand['funcionario_id']?'selected':'' ?>><?= e($employee['nome_completo']) ?></option><?php endforeach; ?></select></div>
<?php if (!empty($demand['anexo_emenda'])): ?><div class="col-12"><a target="_blank" href="<?= url('anexos/demandas/download?demand_id=' . (int)$demand['id']) ?>">Anexo atual</a> <small class="text-muted">(<?= e($demand['anexo_nome_original'] ?? basename((string)$demand['anexo_emenda'])) ?>)</small></div><?php endif; ?>
<div class="col-12"><textarea class="form-control" name="observacao" rows="2"><?= e($demand['observacao'] ?? '') ?></textarea></div>
</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-success">Salvar</button></div></form>
</div></div></div>
<?php endforeach; ?>
</tbody></table></div>

```
### Explicação técnica breve
Implementação compatível com as etapas anteriores, preservando autenticação, CSRF, regras de perfil e consistência entre banco/model/controller/view.
### Validação de compatibilidade
- Sintaxe PHP validada e integração mantida com rotas e views existentes.

## 5. Atualizar views do Funcionário
### Arquivos
- `app/views/funcionario/demandas/index.php`
- `app/views/employee/demands/index.php`
### Conteúdo completo dos arquivos
#### `app/views/funcionario/demandas/index.php`
```php
<?php
$statusLabels = ['pendente' => 'Pendente', 'cadastrado' => 'Cadastrado'];
$processLabels = ['prestacao_de_conta' => 'Prestação de conta', 'cadastro_de_emenda' => 'Cadastro de emenda'];
$amendmentLabels = ['parlamentar' => 'Parlamentar', 'estadual' => 'Estadual', 'municipal' => 'Municipal'];
?>

<h3 class="mb-3">Minhas Demandas</h3>
<div class="table-responsive">
  <table class="table table-striped table-modern align-middle data-table" id="employeeDemandsTable">
    <thead><tr><th>Emenda</th><th>Tipo</th><th>Prazo</th><th>Status</th><th>Anexo</th><th>Ação</th></tr></thead>
    <tbody id="employeeDemandsBody">
      <?php foreach ($demands as $demand): ?>
      <tr>
        <td><strong><?= e($demand['emenda']) ?></strong><br><small class="text-muted"><?= e($demand['nome_politico']) ?></small></td>
        <td><?= e($processLabels[$demand['tipo_processo']] ?? $demand['tipo_processo']) ?> / <?= e($amendmentLabels[$demand['tipo_emenda']] ?? $demand['tipo_emenda']) ?></td>
        <td><?= e(date('d/m/Y H:i', strtotime($demand['data_prazo_resposta']))) ?></td>
        <td><span class="badge bg-secondary"><?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></span></td>
        <td><?php if (!empty($demand['anexo_emenda'])): ?><a target="_blank" href="<?= url('anexos/demandas/download?demand_id=' . (int)$demand['id']) ?>">Abrir</a><?php else: ?>-<?php endif; ?></td>
        <td>
          <form method="post" action="<?= url('funcionario/demandas/status') ?>" class="d-flex gap-2">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= (int)$demand['id'] ?>">
            <select class="form-select form-select-sm" name="status" required>
              <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= $demand['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Atualizar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

```
#### `app/views/employee/demands/index.php`
```php
<?php
$statusLabels = ['pendente' => 'Pendente', 'cadastrado' => 'Cadastrado'];
$processLabels = ['prestacao_de_conta' => 'Prestação de conta', 'cadastro_de_emenda' => 'Cadastro de emenda'];
$amendmentLabels = ['parlamentar' => 'Parlamentar', 'estadual' => 'Estadual', 'municipal' => 'Municipal'];
?>

<h3 class="mb-3">Minhas Demandas</h3>
<div class="table-responsive">
  <table class="table table-striped table-modern align-middle data-table" id="employeeDemandsTable">
    <thead><tr><th>Emenda</th><th>Tipo</th><th>Prazo</th><th>Status</th><th>Anexo</th><th>Ação</th></tr></thead>
    <tbody id="employeeDemandsBody">
      <?php foreach ($demands as $demand): ?>
      <tr>
        <td><strong><?= e($demand['emenda']) ?></strong><br><small class="text-muted"><?= e($demand['nome_politico']) ?></small></td>
        <td><?= e($processLabels[$demand['tipo_processo']] ?? $demand['tipo_processo']) ?> / <?= e($amendmentLabels[$demand['tipo_emenda']] ?? $demand['tipo_emenda']) ?></td>
        <td><?= e(date('d/m/Y H:i', strtotime($demand['data_prazo_resposta']))) ?></td>
        <td><span class="badge bg-secondary"><?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></span></td>
        <td><?php if (!empty($demand['anexo_emenda'])): ?><a target="_blank" href="<?= url('anexos/demandas/download?demand_id=' . (int)$demand['id']) ?>">Abrir</a><?php else: ?>-<?php endif; ?></td>
        <td>
          <form method="post" action="<?= url('funcionario/demandas/status') ?>" class="d-flex gap-2">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= (int)$demand['id'] ?>">
            <select class="form-select form-select-sm" name="status" required>
              <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= $demand['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Atualizar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

```
### Explicação técnica breve
Implementação compatível com as etapas anteriores, preservando autenticação, CSRF, regras de perfil e consistência entre banco/model/controller/view.
### Validação de compatibilidade
- Sintaxe PHP validada e integração mantida com rotas e views existentes.

## 6. Implementar upload seguro
### Arquivos
- `app/Services/SecureAttachmentService.php`
- `app/Controllers/AttachmentController.php`
### Conteúdo completo dos arquivos
#### `app/Services/SecureAttachmentService.php`
```php
<?php

namespace App\Services;

class SecureAttachmentService
{
    private const MAX_SIZE = 5242880; // 5MB
    private const BASE_DIR = 'storage/uploads/emendas';

    /**
     * @param array<string,mixed>|null $file
     * @return array{ok:bool,path:?string,original_name:?string,mime:?string,errors:array<int,string>}
     */
    public function store(?array $file): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => []];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha no upload do anexo.']];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_SIZE) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Arquivo inválido. Limite máximo: 5MB.']];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Upload não confiável.']];
        }

        $originalName = $this->sanitizeOriginalName((string)($file['name'] ?? 'documento'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'docx'], true)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Apenas arquivos PDF e DOCX são permitidos.']];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        if (!$this->isValidMime($extension, $mime)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Tipo MIME inválido para o arquivo enviado.']];
        }

        // Anti-mascaramento adicional por assinatura de arquivo.
        if (!$this->matchesBinarySignature($extension, $tmp)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Conteúdo do arquivo não corresponde ao tipo permitido.']];
        }

        $base = BASE_PATH . '/' . self::BASE_DIR;
        $metaDir = $base . '/.meta';

        if (!is_dir($base) && !mkdir($base, 0750, true) && !is_dir($base)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha ao preparar diretório de anexos.']];
        }
        if (!is_dir($metaDir) && !mkdir($metaDir, 0750, true) && !is_dir($metaDir)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha ao preparar diretório de metadados.']];
        }

        $this->ensureDenyDirectAccess($base);

        $secureName = 'anexo_' . date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $destination = $base . '/' . $secureName;
        if (!move_uploaded_file($tmp, $destination)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha ao salvar arquivo no servidor.']];
        }

        $relativePath = self::BASE_DIR . '/' . $secureName;

        $meta = [
            'original_name' => $originalName,
            'mime' => $mime,
            'uploaded_at' => date('c'),
        ];
        @file_put_contents($metaDir . '/' . $secureName . '.json', json_encode($meta, JSON_UNESCAPED_UNICODE));

        return ['ok' => true, 'path' => $relativePath, 'original_name' => $originalName, 'mime' => $mime, 'errors' => []];
    }

    public function resolveAbsolutePath(string $relativePath): ?string
    {
        $relativePath = ltrim($relativePath, '/');
        if (!str_starts_with($relativePath, self::BASE_DIR . '/')) {
            return null;
        }

        $absolute = BASE_PATH . '/' . $relativePath;
        if (!is_file($absolute)) {
            return null;
        }

        return $absolute;
    }

    public function originalName(string $relativePath): string
    {
        $fileName = basename($relativePath);
        $metaPath = BASE_PATH . '/' . self::BASE_DIR . '/.meta/' . $fileName . '.json';
        if (is_file($metaPath)) {
            $raw = @file_get_contents($metaPath);
            if (is_string($raw) && $raw !== '') {
                $json = json_decode($raw, true);
                if (is_array($json) && !empty($json['original_name'])) {
                    return (string)$json['original_name'];
                }
            }
        }

        return $fileName;
    }

    private function sanitizeOriginalName(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9._-]/', '_', trim($name)) ?? 'documento';
        return mb_substr($clean, 0, 150);
    }

    private function isValidMime(string $ext, string $mime): bool
    {
        if ($ext === 'pdf') {
            return $mime === 'application/pdf';
        }

        if ($ext === 'docx') {
            return in_array($mime, ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true);
        }

        return false;
    }

    private function matchesBinarySignature(string $ext, string $tmpPath): bool
    {
        $sample = @file_get_contents($tmpPath, false, null, 0, 8);
        if (!is_string($sample)) {
            return false;
        }

        if ($ext === 'pdf') {
            return str_starts_with($sample, "%PDF");
        }

        if ($ext === 'docx') {
            if (!str_starts_with($sample, "PK")) {
                return false;
            }

            $zip = new \ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                return false;
            }
            $hasManifest = $zip->locateName('[Content_Types].xml') !== false;
            $hasWordDoc = $zip->locateName('word/document.xml') !== false;
            $zip->close();
            return $hasManifest && $hasWordDoc;
        }

        return false;
    }

    private function ensureDenyDirectAccess(string $directory): void
    {
        $htaccess = $directory . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }
    }
}

```
#### `app/Controllers/AttachmentController.php`
```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\SystemLog;
use App\Services\SecureAttachmentService;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly SecureAttachmentService $attachments = new SecureAttachmentService()
    ) {
    }

    public function downloadDemandAttachment(): void
    {
        $this->requireAuth();

        $demandId = (int)($_GET['demand_id'] ?? 0);
        if ($demandId <= 0) {
            http_response_code(400);
            echo 'Anexo inválido.';
            return;
        }

        $demand = $this->demands->findById($demandId);
        if (!$demand || empty($demand['anexo_emenda'])) {
            http_response_code(404);
            echo 'Anexo não encontrado.';
            return;
        }

        $role = (string)($_SESSION['user']['role'] ?? '');
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($role !== 'master' && (int)$demand['funcionario_id'] !== $userId) {
            http_response_code(403);
            echo 'Acesso negado ao anexo.';
            return;
        }

        $relativePath = (string)$demand['anexo_emenda'];
        $file = $this->attachments->resolveAbsolutePath($relativePath);
        if ($file === null) {
            http_response_code(404);
            echo 'Arquivo indisponível.';
            return;
        }

        $originalName = (string)($demand['anexo_nome_original'] ?? '');
        if ($originalName === '') {
            $originalName = $this->attachments->originalName($relativePath);
        }
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $inline = (($_GET['inline'] ?? '0') === '1') && $extension === 'pdf';

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($file);

        $this->logs->create([
            'usuario_id' => $userId,
            'acao' => 'download_anexo',
            'entidade' => 'demandas',
            'entidade_id' => $demandId,
            'descricao' => 'Download/visualização de anexo da demanda',
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . (string)filesize($file));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes($originalName) . '"');

        readfile($file);
        exit;
    }
}

```
### Explicação técnica breve
Implementação compatível com as etapas anteriores, preservando autenticação, CSRF, regras de perfil e consistência entre banco/model/controller/view.
### Validação de compatibilidade
- Sintaxe PHP validada e integração mantida com rotas e views existentes.

## 7. Implementar notificações automáticas
### Arquivos
- `app/Controllers/NotificationController.php`
- `app/Controllers/PanelSyncController.php`
### Conteúdo completo dos arquivos
#### `app/Controllers/NotificationController.php`
```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function __construct(
        private readonly Notification $notifications = new Notification()
    ) {
    }

    public function poll(): void
    {
        $this->requireAuth();

        header('Content-Type: application/json; charset=utf-8');

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => 'Sessão inválida.']);
            exit;
        }

        $afterId = max(0, (int)($_GET['after_id'] ?? 0));
        $role = (string)($_SESSION['user']['role'] ?? '');

        $items = $this->notifications->latestForUser($userId, $afterId, 20);
        $items = array_map(function (array $item) use ($role): array {
            $item['link'] = $this->resolveNotificationLink($item, $role);
            return $item;
        }, $items);

        echo json_encode([
            'ok' => true,
            'unread_count' => $this->notifications->unreadCount($userId),
            'latest_id' => $this->notifications->latestIdForUser($userId),
            'items' => $items,
        ]);
        exit;
    }

    public function markReadAjax(): void
    {
        $this->requireAuth();

        header('Content-Type: application/json; charset=utf-8');

        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $notificationId = (int)($_POST['id'] ?? 0);

        if ($userId <= 0 || $notificationId <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Parâmetros inválidos.']);
            exit;
        }

        $ok = $this->notifications->markRead($notificationId, $userId);

        echo json_encode([
            'ok' => $ok,
            'unread_count' => $this->notifications->unreadCount($userId),
        ]);
        exit;
    }

    private function resolveNotificationLink(array $item, string $role): string
    {
        $table = (string)($item['referencia_tabela'] ?? '');

        if ($table === 'demandas') {
            return url($role === 'master' ? 'master/demands' : 'funcionario/demandas');
        }

        return url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes');
    }
}

```
#### `app/Controllers/PanelSyncController.php`
```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Services\NotificationDeadlineService;

class PanelSyncController extends Controller
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification(),
        private readonly NotificationDeadlineService $deadlineService = new NotificationDeadlineService()
    ) {
    }

    public function master(): void
    {
        $this->requireAuth('master');

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $this->deadlineService->runAutomationForMaster($userId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'role' => 'master',
            'timestamp' => date('c'),
            'unread_count' => $this->notifications->unreadCount($userId),
            'latest_notification_id' => $this->notifications->latestIdForUser($userId),
            'notifications' => $this->notifications->forUser($userId, 8),
            'demands' => $this->demands->paginatedFiltered([], 25, 0),
            'stats' => $this->demands->stats(),
            'upcoming' => $this->demands->upcomingDeadlines(8),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function employee(): void
    {
        $this->requireAuth('funcionario');

        $userId = (int)($_SESSION['user']['id'] ?? 0);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'role' => 'funcionario',
            'timestamp' => date('c'),
            'unread_count' => $this->notifications->unreadCount($userId),
            'latest_notification_id' => $this->notifications->latestIdForUser($userId),
            'notifications' => $this->notifications->forUser($userId, 8),
            'demands' => $this->demands->byEmployee($userId),
            'summary' => $this->demands->employeeSummary($userId),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

```
### Explicação técnica breve
Implementação compatível com as etapas anteriores, preservando autenticação, CSRF, regras de perfil e consistência entre banco/model/controller/view.
### Validação de compatibilidade
- Sintaxe PHP validada e integração mantida com rotas e views existentes.

## 8. Implementar atualização automática via AJAX polling
### Arquivos
- `assets/js/app.js`
- `public/assets/js/app.js`
- `app/views/layouts/header.php`
- `app/config/routes.php`
### Conteúdo completo dos arquivos
#### `assets/js/app.js`
```js
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initCalendar();
  initDataTable();
  initRealtimePanelSync();
});

function initSidebar() { /* unchanged */
  const appShell = document.getElementById('appShell');
  const sidebar = document.getElementById('sidebarNav');
  const sidebarToggle = document.getElementById('sidebarToggle');
  if (!appShell || !sidebar || !sidebarToggle) return;
  sidebarToggle.addEventListener('click', () => appShell.classList.toggle('sidebar-open'));
  document.addEventListener('click', (event) => {
    if (window.innerWidth > 991) return;
    if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) appShell.classList.remove('sidebar-open');
  });
}

function initCalendar() {
  const calendarEl = document.getElementById('calendar');
  if (!calendarEl || !window.FullCalendar) return;
  const events = JSON.parse(calendarEl.dataset.events || '[]');
  new FullCalendar.Calendar(calendarEl, { initialView: 'dayGridMonth', locale: 'pt-br', events, height: 'auto', dayMaxEvents: true, buttonText: { today: 'Hoje' } }).render();
}

function initDataTable() {
  if (!window.jQuery || !window.jQuery.fn.DataTable) return;
  window.jQuery('.data-table').DataTable({ pageLength: 10, order: [], responsive: true, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' } });
}

function initRealtimePanelSync() {
  const appShell = document.getElementById('appShell');
  if (!appShell) return;

  const syncUrl = appShell.dataset.syncUrl;
  const readUrl = appShell.dataset.notificationReadUrl;
  const statusUrl = appShell.dataset.employeeStatusUrl;
  const csrfToken = appShell.dataset.csrfToken || '';
  const fallbackListUrl = appShell.dataset.notificationBaseUrl || '#';
  const masterDemandsUrl = appShell.dataset.masterDemandsUrl || fallbackListUrl;
  const employeeDemandsUrl = appShell.dataset.employeeDemandsUrl || fallbackListUrl;
  const attachmentDownloadUrl = appShell.dataset.attachmentDownloadUrl || '';
  const toastArea = document.getElementById('toastArea');

  if (!syncUrl || !readUrl) return;

  let lastNotificationId = Number(appShell.dataset.notificationLatestId || 0);
  let nativeAllowed = false;

  if ('Notification' in window) {
    if (Notification.permission === 'granted') nativeAllowed = true;
    else if (Notification.permission === 'default') {
      Notification.requestPermission().then((p) => { nativeAllowed = p === 'granted'; }).catch(() => {});
    }
  }

  const poll = async () => {
    try {
      const response = await fetch(syncUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!response.ok) return;
      const payload = await response.json();
      if (!payload.ok) return;

      updateUnreadBadge(payload.unread_count || 0);
      renderHeaderNotifications(payload.notifications || [], fallbackListUrl);

      const newItems = (payload.notifications || []).filter((n) => Number(n.id || 0) > lastNotificationId);
      if (newItems.length) {
        lastNotificationId = Math.max(lastNotificationId, ...newItems.map((n) => Number(n.id || 0)));
        newItems.reverse().forEach((item) => {
          const destination = (item.referencia_tabela === 'demandas')
            ? (payload.role === 'master' ? masterDemandsUrl : employeeDemandsUrl)
            : fallbackListUrl;
          showToast(item, destination, toastArea, () => markNotificationRead(readUrl, csrfToken, Number(item.id || 0)));
          if (nativeAllowed) new Notification(item.titulo || 'Notificação', { body: item.mensagem || '' });
        });
      }

      if (payload.role === 'master') {
        renderMasterDemands(payload.demands || [], masterDemandsUrl, attachmentDownloadUrl);
        renderMasterStats(payload.stats || {});
        renderMasterUpcoming(payload.upcoming || []);
        renderMasterDashboardNotifications(payload.notifications || []);
      } else {
        renderEmployeeDemands(payload.demands || [], statusUrl, csrfToken, attachmentDownloadUrl);
        renderEmployeeSummary(payload.summary || {});
      }
    } catch (error) {
      console.warn('Falha no sync parcial:', error);
    }
  };

  poll();
  setInterval(poll, 15000);
}

function renderHeaderNotifications(items, fallbackUrl) {
  const box = document.getElementById('headerNotificationList');
  if (!box) return;
  if (!items.length) {
    box.innerHTML = '<div class="px-3 py-3 text-muted small">Sem notificações recentes.</div>';
    return;
  }
  box.innerHTML = items.map((n) => `
    <a class="dropdown-item py-2" href="${fallbackUrl}">
      <div class="small fw-semibold">${escapeHtml(n.titulo || 'Notificação')}</div>
      <div class="small text-muted">${escapeHtml(n.mensagem || '')}</div>
    </a>`).join('');
}

function renderMasterDemands(demands, masterDemandsUrl, attachmentDownloadUrl) {
  const tbody = document.getElementById('masterDemandsBody');
  if (!tbody) return;
  tbody.innerHTML = demands.map((d) => `
    <tr>
      <td><strong>${escapeHtml(d.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(d.funcionario_nome || '-')}</small></td>
      <td>${escapeHtml(d.funcionario_nome || '-')}</td>
      <td>${formatDate(d.data_prazo_resposta)}</td>
      <td><span class="badge bg-secondary">${escapeHtml(d.status || '')}</span></td>
      <td>${d.anexo_emenda ? `<a class=\"btn btn-sm btn-outline-dark\" href=\"${attachmentDownloadUrl}?inline=1&demand_id=${Number(d.id||0)}\" target=\"_blank\">Visualizar</a>` : '<span class=\"text-muted\">-</span>'}</td>
      <td><a class="btn btn-sm btn-outline-primary" href="${masterDemandsUrl}">Abrir painel</a></td>
    </tr>
  `).join('');
}

function renderEmployeeDemands(demands, statusUrl, csrf, attachmentDownloadUrl) {
  const tbody = document.getElementById('employeeDemandsBody');
  if (!tbody) return;
  tbody.innerHTML = demands.map((d) => `
    <tr>
      <td><strong>${escapeHtml(d.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(d.nome_politico || '')}</small></td>
      <td>${escapeHtml(d.tipo_processo || '')} / ${escapeHtml(d.tipo_emenda || '')}</td>
      <td>${formatDate(d.data_prazo_resposta)}</td>
      <td><span class="badge bg-secondary">${escapeHtml(d.status || '')}</span></td>
      <td>${d.anexo_emenda ? `<a target=\"_blank\" href=\"${attachmentDownloadUrl}?demand_id=${Number(d.id||0)}\">Abrir</a>` : '-'}</td>
      <td>
        <form method="post" action="${statusUrl || '/emendas/funcionario/demandas/status'}" class="d-flex gap-2">
          <input type="hidden" name="_csrf" value="${escapeHtml(csrf)}">
          <input type="hidden" name="id" value="${Number(d.id||0)}">
          <select class="form-select form-select-sm" name="status" required>
            <option value="pendente" ${d.status === 'pendente' ? 'selected' : ''}>Pendente</option>
            <option value="cadastrado" ${d.status === 'cadastrado' ? 'selected' : ''}>Cadastrado</option>
          </select>
          <button class="btn btn-sm btn-primary">Atualizar</button>
        </form>
      </td>
    </tr>
  `).join('');
}

function renderMasterStats(stats) {
  const map = [
    ['masterStatTotal', stats.total || 0],
    ['masterStatPendente', stats.pendente || 0],
    ['masterStatCadastrado', stats.cadastrado || 0],
  ];
  map.forEach(([id, val]) => { const el = document.getElementById(id); if (el) el.textContent = String(val); });
}

function renderEmployeeSummary(summary) {
  const map = [['employeeSummaryTotal', summary.total || 0], ['employeeSummaryPendente', summary.pendente || 0], ['employeeSummaryCadastrado', summary.cadastrado || 0]];
  map.forEach(([id, val]) => { const el = document.getElementById(id); if (el) el.textContent = String(val); });
}

function renderMasterUpcoming(items) {
  const ul = document.getElementById('masterUpcomingList');
  if (!ul) return;
  ul.innerHTML = items.length ? items.map((i) => `<li class="list-group-item px-0 d-flex justify-content-between align-items-start"><div><strong>${escapeHtml(i.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(i.funcionario_nome || '')}</small></div><small class="badge bg-dark">${formatDate(i.data_prazo_resposta, true)}</small></li>`).join('') : '<li class="list-group-item px-0 text-muted">Nenhuma demanda com prazo crítico.</li>';
}

function renderMasterDashboardNotifications(items) {
  const ul = document.getElementById('masterDashboardNotifications');
  if (!ul) return;
  ul.innerHTML = items.length ? items.map((n) => `<li class="list-group-item px-0"><strong>${escapeHtml(n.titulo || '')}</strong><br><small class="text-muted">${escapeHtml(n.mensagem || '')}</small></li>`).join('') : '<li class="list-group-item px-0 text-muted">Sem notificações recentes.</li>';
}

async function markNotificationRead(readUrl, csrfToken, id) {
  if (!id) return;
  const body = new URLSearchParams(); body.set('_csrf', csrfToken); body.set('id', String(id));
  try {
    const response = await fetch(readUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: body.toString() });
    if (!response.ok) return;
    const payload = await response.json();
    if (payload.ok) updateUnreadBadge(payload.unread_count || 0);
  } catch (_) {}
}

function updateUnreadBadge(count) {
  const btn = document.querySelector('button[data-bs-toggle="dropdown"]'); if (!btn) return;
  let badge = document.getElementById('headerUnreadBadge');
  if (count <= 0) { if (badge) badge.remove(); return; }
  if (!badge) { badge = document.createElement('span'); badge.id = 'headerUnreadBadge'; badge.className = 'badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle'; btn.appendChild(badge); }
  badge.textContent = String(count);
}

function showToast(item, destinationUrl, toastArea, onOpen) {
  if (!toastArea || !window.bootstrap) return;
  const title = escapeHtml(item.titulo || 'Notificação');
  const message = escapeHtml(item.mensagem || 'Nova atualização disponível.');
  const timestamp = new Date(item.created_at || Date.now()).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  const wrapper = document.createElement('div');
  wrapper.innerHTML = `<div class="toast border-0 shadow-sm" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000"><div class="toast-header"><strong class="me-auto">${title}</strong><small>${timestamp}</small><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div><div class="toast-body"><a class="text-decoration-none" href="${destinationUrl}">${message}</a></div></div>`;
  const toastElement = wrapper.firstElementChild; toastArea.appendChild(toastElement);
  const anchor = toastElement.querySelector('a'); if (anchor && typeof onOpen === 'function') anchor.addEventListener('click', () => onOpen());
  new window.bootstrap.Toast(toastElement).show();
  toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
}

function formatDate(value, compact = false) {
  if (!value) return '-';
  const date = new Date(value.replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return escapeHtml(value);
  return date.toLocaleString('pt-BR', compact ? { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' } : { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(value) {
  return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

```
#### `public/assets/js/app.js`
```js
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initCalendar();
  initDataTable();
  initRealtimePanelSync();
});

function initSidebar() { /* unchanged */
  const appShell = document.getElementById('appShell');
  const sidebar = document.getElementById('sidebarNav');
  const sidebarToggle = document.getElementById('sidebarToggle');
  if (!appShell || !sidebar || !sidebarToggle) return;
  sidebarToggle.addEventListener('click', () => appShell.classList.toggle('sidebar-open'));
  document.addEventListener('click', (event) => {
    if (window.innerWidth > 991) return;
    if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) appShell.classList.remove('sidebar-open');
  });
}

function initCalendar() {
  const calendarEl = document.getElementById('calendar');
  if (!calendarEl || !window.FullCalendar) return;
  const events = JSON.parse(calendarEl.dataset.events || '[]');
  new FullCalendar.Calendar(calendarEl, { initialView: 'dayGridMonth', locale: 'pt-br', events, height: 'auto', dayMaxEvents: true, buttonText: { today: 'Hoje' } }).render();
}

function initDataTable() {
  if (!window.jQuery || !window.jQuery.fn.DataTable) return;
  window.jQuery('.data-table').DataTable({ pageLength: 10, order: [], responsive: true, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' } });
}

function initRealtimePanelSync() {
  const appShell = document.getElementById('appShell');
  if (!appShell) return;

  const syncUrl = appShell.dataset.syncUrl;
  const readUrl = appShell.dataset.notificationReadUrl;
  const statusUrl = appShell.dataset.employeeStatusUrl;
  const csrfToken = appShell.dataset.csrfToken || '';
  const fallbackListUrl = appShell.dataset.notificationBaseUrl || '#';
  const masterDemandsUrl = appShell.dataset.masterDemandsUrl || fallbackListUrl;
  const employeeDemandsUrl = appShell.dataset.employeeDemandsUrl || fallbackListUrl;
  const attachmentDownloadUrl = appShell.dataset.attachmentDownloadUrl || '';
  const toastArea = document.getElementById('toastArea');

  if (!syncUrl || !readUrl) return;

  let lastNotificationId = Number(appShell.dataset.notificationLatestId || 0);
  let nativeAllowed = false;

  if ('Notification' in window) {
    if (Notification.permission === 'granted') nativeAllowed = true;
    else if (Notification.permission === 'default') {
      Notification.requestPermission().then((p) => { nativeAllowed = p === 'granted'; }).catch(() => {});
    }
  }

  const poll = async () => {
    try {
      const response = await fetch(syncUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!response.ok) return;
      const payload = await response.json();
      if (!payload.ok) return;

      updateUnreadBadge(payload.unread_count || 0);
      renderHeaderNotifications(payload.notifications || [], fallbackListUrl);

      const newItems = (payload.notifications || []).filter((n) => Number(n.id || 0) > lastNotificationId);
      if (newItems.length) {
        lastNotificationId = Math.max(lastNotificationId, ...newItems.map((n) => Number(n.id || 0)));
        newItems.reverse().forEach((item) => {
          const destination = (item.referencia_tabela === 'demandas')
            ? (payload.role === 'master' ? masterDemandsUrl : employeeDemandsUrl)
            : fallbackListUrl;
          showToast(item, destination, toastArea, () => markNotificationRead(readUrl, csrfToken, Number(item.id || 0)));
          if (nativeAllowed) new Notification(item.titulo || 'Notificação', { body: item.mensagem || '' });
        });
      }

      if (payload.role === 'master') {
        renderMasterDemands(payload.demands || [], masterDemandsUrl, attachmentDownloadUrl);
        renderMasterStats(payload.stats || {});
        renderMasterUpcoming(payload.upcoming || []);
        renderMasterDashboardNotifications(payload.notifications || []);
      } else {
        renderEmployeeDemands(payload.demands || [], statusUrl, csrfToken, attachmentDownloadUrl);
        renderEmployeeSummary(payload.summary || {});
      }
    } catch (error) {
      console.warn('Falha no sync parcial:', error);
    }
  };

  poll();
  setInterval(poll, 15000);
}

function renderHeaderNotifications(items, fallbackUrl) {
  const box = document.getElementById('headerNotificationList');
  if (!box) return;
  if (!items.length) {
    box.innerHTML = '<div class="px-3 py-3 text-muted small">Sem notificações recentes.</div>';
    return;
  }
  box.innerHTML = items.map((n) => `
    <a class="dropdown-item py-2" href="${fallbackUrl}">
      <div class="small fw-semibold">${escapeHtml(n.titulo || 'Notificação')}</div>
      <div class="small text-muted">${escapeHtml(n.mensagem || '')}</div>
    </a>`).join('');
}

function renderMasterDemands(demands, masterDemandsUrl, attachmentDownloadUrl) {
  const tbody = document.getElementById('masterDemandsBody');
  if (!tbody) return;
  tbody.innerHTML = demands.map((d) => `
    <tr>
      <td><strong>${escapeHtml(d.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(d.funcionario_nome || '-')}</small></td>
      <td>${escapeHtml(d.funcionario_nome || '-')}</td>
      <td>${formatDate(d.data_prazo_resposta)}</td>
      <td><span class="badge bg-secondary">${escapeHtml(d.status || '')}</span></td>
      <td>${d.anexo_emenda ? `<a class=\"btn btn-sm btn-outline-dark\" href=\"${attachmentDownloadUrl}?inline=1&demand_id=${Number(d.id||0)}\" target=\"_blank\">Visualizar</a>` : '<span class=\"text-muted\">-</span>'}</td>
      <td><a class="btn btn-sm btn-outline-primary" href="${masterDemandsUrl}">Abrir painel</a></td>
    </tr>
  `).join('');
}

function renderEmployeeDemands(demands, statusUrl, csrf, attachmentDownloadUrl) {
  const tbody = document.getElementById('employeeDemandsBody');
  if (!tbody) return;
  tbody.innerHTML = demands.map((d) => `
    <tr>
      <td><strong>${escapeHtml(d.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(d.nome_politico || '')}</small></td>
      <td>${escapeHtml(d.tipo_processo || '')} / ${escapeHtml(d.tipo_emenda || '')}</td>
      <td>${formatDate(d.data_prazo_resposta)}</td>
      <td><span class="badge bg-secondary">${escapeHtml(d.status || '')}</span></td>
      <td>${d.anexo_emenda ? `<a target=\"_blank\" href=\"${attachmentDownloadUrl}?demand_id=${Number(d.id||0)}\">Abrir</a>` : '-'}</td>
      <td>
        <form method="post" action="${statusUrl || '/emendas/funcionario/demandas/status'}" class="d-flex gap-2">
          <input type="hidden" name="_csrf" value="${escapeHtml(csrf)}">
          <input type="hidden" name="id" value="${Number(d.id||0)}">
          <select class="form-select form-select-sm" name="status" required>
            <option value="pendente" ${d.status === 'pendente' ? 'selected' : ''}>Pendente</option>
            <option value="cadastrado" ${d.status === 'cadastrado' ? 'selected' : ''}>Cadastrado</option>
          </select>
          <button class="btn btn-sm btn-primary">Atualizar</button>
        </form>
      </td>
    </tr>
  `).join('');
}

function renderMasterStats(stats) {
  const map = [
    ['masterStatTotal', stats.total || 0],
    ['masterStatPendente', stats.pendente || 0],
    ['masterStatCadastrado', stats.cadastrado || 0],
  ];
  map.forEach(([id, val]) => { const el = document.getElementById(id); if (el) el.textContent = String(val); });
}

function renderEmployeeSummary(summary) {
  const map = [['employeeSummaryTotal', summary.total || 0], ['employeeSummaryPendente', summary.pendente || 0], ['employeeSummaryCadastrado', summary.cadastrado || 0]];
  map.forEach(([id, val]) => { const el = document.getElementById(id); if (el) el.textContent = String(val); });
}

function renderMasterUpcoming(items) {
  const ul = document.getElementById('masterUpcomingList');
  if (!ul) return;
  ul.innerHTML = items.length ? items.map((i) => `<li class="list-group-item px-0 d-flex justify-content-between align-items-start"><div><strong>${escapeHtml(i.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(i.funcionario_nome || '')}</small></div><small class="badge bg-dark">${formatDate(i.data_prazo_resposta, true)}</small></li>`).join('') : '<li class="list-group-item px-0 text-muted">Nenhuma demanda com prazo crítico.</li>';
}

function renderMasterDashboardNotifications(items) {
  const ul = document.getElementById('masterDashboardNotifications');
  if (!ul) return;
  ul.innerHTML = items.length ? items.map((n) => `<li class="list-group-item px-0"><strong>${escapeHtml(n.titulo || '')}</strong><br><small class="text-muted">${escapeHtml(n.mensagem || '')}</small></li>`).join('') : '<li class="list-group-item px-0 text-muted">Sem notificações recentes.</li>';
}

async function markNotificationRead(readUrl, csrfToken, id) {
  if (!id) return;
  const body = new URLSearchParams(); body.set('_csrf', csrfToken); body.set('id', String(id));
  try {
    const response = await fetch(readUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: body.toString() });
    if (!response.ok) return;
    const payload = await response.json();
    if (payload.ok) updateUnreadBadge(payload.unread_count || 0);
  } catch (_) {}
}

function updateUnreadBadge(count) {
  const btn = document.querySelector('button[data-bs-toggle="dropdown"]'); if (!btn) return;
  let badge = document.getElementById('headerUnreadBadge');
  if (count <= 0) { if (badge) badge.remove(); return; }
  if (!badge) { badge = document.createElement('span'); badge.id = 'headerUnreadBadge'; badge.className = 'badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle'; btn.appendChild(badge); }
  badge.textContent = String(count);
}

function showToast(item, destinationUrl, toastArea, onOpen) {
  if (!toastArea || !window.bootstrap) return;
  const title = escapeHtml(item.titulo || 'Notificação');
  const message = escapeHtml(item.mensagem || 'Nova atualização disponível.');
  const timestamp = new Date(item.created_at || Date.now()).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  const wrapper = document.createElement('div');
  wrapper.innerHTML = `<div class="toast border-0 shadow-sm" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000"><div class="toast-header"><strong class="me-auto">${title}</strong><small>${timestamp}</small><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div><div class="toast-body"><a class="text-decoration-none" href="${destinationUrl}">${message}</a></div></div>`;
  const toastElement = wrapper.firstElementChild; toastArea.appendChild(toastElement);
  const anchor = toastElement.querySelector('a'); if (anchor && typeof onOpen === 'function') anchor.addEventListener('click', () => onOpen());
  new window.bootstrap.Toast(toastElement).show();
  toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
}

function formatDate(value, compact = false) {
  if (!value) return '-';
  const date = new Date(value.replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return escapeHtml(value);
  return date.toLocaleString('pt-BR', compact ? { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' } : { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(value) {
  return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

```
#### `app/views/layouts/header.php`
```php
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<?php if (!empty($_SESSION['user'])): ?>
<?php
  $role = $_SESSION['user']['role'] ?? '';
  $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
  $basePath = rtrim(APP_BASE_PATH, '/');
  if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
      $requestPath = substr($requestPath, strlen($basePath));
  }
  $requestPath = '/' . ltrim($requestPath, '/');

  $navItems = $role === 'master'
    ? [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => '/master/dashboard'],
        ['label' => 'Funcionários', 'icon' => 'bi-people', 'path' => '/master/employees'],
        ['label' => 'Demandas', 'icon' => 'bi-list-task', 'path' => '/master/demands'],
        ['label' => 'Notificações', 'icon' => 'bi-bell', 'path' => '/master/notifications'],
      ]
    : [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => '/funcionario/dashboard'],
        ['label' => 'Minhas Demandas', 'icon' => 'bi-list-check', 'path' => '/funcionario/demandas'],
        ['label' => 'Notificações', 'icon' => 'bi-bell', 'path' => '/funcionario/notificacoes'],
      ];
?>
<div class="app-shell" id="appShell" data-polling-url="<?= url('api/notifications/poll') ?>" data-sync-url="<?= url($role === 'master' ? 'api/master/sync' : 'api/funcionario/sync') ?>" data-notification-read-url="<?= url('api/notifications/read') ?>" data-notification-base-url="<?= url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes') ?>" data-notification-latest-id="<?= (int)($headerNotifications[0]['id'] ?? 0) ?>" data-employee-status-url="<?= url('funcionario/demandas/status') ?>" data-master-demands-url="<?= url('master/demands') ?>" data-employee-demands-url="<?= url('funcionario/demandas') ?>" data-attachment-download-url="<?= url('anexos/demandas/download') ?>" data-csrf-token="<?= csrf_token() ?>">
  <aside class="sidebar" id="sidebarNav">
    <a class="brand" href="<?= url($role === 'master' ? 'master/dashboard' : 'funcionario/dashboard') ?>">
      <i class="bi bi-building"></i> Emendas Gov
    </a>

    <nav class="nav flex-column gap-1">
      <?php foreach ($navItems as $item): ?>
        <?php $active = str_starts_with($requestPath, $item['path']); ?>
        <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= url(ltrim($item['path'], '/')) ?>">
          <i class="bi <?= e($item['icon']) ?>"></i> <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="main-wrap">
    <header class="topbar d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm d-lg-none" id="sidebarToggle" type="button" aria-label="Abrir menu">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <small class="text-muted d-block text-uppercase fw-semibold">Painel institucional</small>
          <strong><?= e($_SESSION['user']['name']) ?></strong>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
          <button class="btn btn-outline-primary btn-sm position-relative" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <?php if ((int)$headerUnreadCount > 0): ?>
              <span id="headerUnreadBadge" class="badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle"><?= (int)$headerUnreadCount ?></span>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-0 shadow notification-menu">
            <div class="p-3 border-bottom">
              <strong>Notificações</strong>
            </div>
            <div class="notification-list" id="headerNotificationList">
              <?php if (!empty($headerNotifications)): ?>
                <?php foreach ($headerNotifications as $headerNotification): ?>
                  <a class="dropdown-item py-2" href="<?= url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes') ?>">
                    <div class="small fw-semibold"><?= e($headerNotification['titulo']) ?></div>
                    <div class="small text-muted"><?= e($headerNotification['mensagem']) ?></div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="px-3 py-3 text-muted small">Sem notificações recentes.</div>
              <?php endif; ?>
            </div>
            <a class="dropdown-item text-center py-2 border-top" href="<?= url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes') ?>">Ver todas</a>
          </div>
        </div>

        <form method="post" action="<?= url('logout') ?>" class="mb-0">
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
          <button class="btn btn-dark btn-sm"><i class="bi bi-box-arrow-right"></i> Sair</button>
        </form>
      </div>
    </header>

    <main class="container-fluid p-3 p-lg-4">
<?php else: ?>
<main class="container py-4">
<?php endif; ?>
<?php if ($error = flash('error')): ?><div class="alert alert-danger shadow-sm border-0"><?= e($error) ?></div><?php endif; ?>
<?php if ($success = flash('success')): ?><div class="alert alert-success shadow-sm border-0"><?= e($success) ?></div><?php endif; ?>

<div id="toastArea" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080"></div>

```
#### `app/config/routes.php`
```php
<?php

use App\Controllers\AttachmentController;
use App\Controllers\AuthController;
use App\Controllers\EmployeeController;
use App\Controllers\FuncionarioController;
use App\Controllers\MasterController;
use App\Controllers\NotificationController;
use App\Controllers\PanelSyncController;

$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/change-password', [AuthController::class, 'showChangePassword']);
$router->post('/change-password', [AuthController::class, 'changePassword']);
$router->post('/logout', [AuthController::class, 'logout']);

// Área Master
$router->get('/master/dashboard', [MasterController::class, 'dashboard']);
$router->get('/master/employees', [MasterController::class, 'employees']);
$router->post('/master/employees/create', [MasterController::class, 'createEmployee']);
$router->post('/master/employees/update', [MasterController::class, 'updateEmployee']);
$router->post('/master/employees/password', [MasterController::class, 'changeEmployeePassword']);
$router->post('/master/employees/toggle', [MasterController::class, 'toggleEmployee']);
$router->post('/master/employees/delete', [MasterController::class, 'deleteEmployee']);
$router->get('/master/demands', [MasterController::class, 'demands']);
$router->post('/master/demands/create', [MasterController::class, 'createDemand']);
$router->post('/master/demands/update', [MasterController::class, 'updateDemand']);
$router->post('/master/demands/delete', [MasterController::class, 'deleteDemand']);
$router->get('/master/notifications', [MasterController::class, 'notifications']);
$router->post('/master/notifications/read', [MasterController::class, 'readNotification']);

// Área Funcionário (nomenclatura oficial)
$router->get('/funcionario/dashboard', [FuncionarioController::class, 'dashboard']);
$router->get('/funcionario/demandas', [FuncionarioController::class, 'demands']);
$router->post('/funcionario/demandas/concluir', [FuncionarioController::class, 'completeDemand']);
$router->post('/funcionario/demandas/status', [FuncionarioController::class, 'updateStatus']);
$router->get('/funcionario/notificacoes', [FuncionarioController::class, 'notifications']);
$router->post('/funcionario/notificacoes/lida', [FuncionarioController::class, 'readNotification']);

// Compatibilidade retroativa com rotas antigas /employee
$router->get('/employee/dashboard', [EmployeeController::class, 'dashboard']);
$router->get('/employee/demands', [EmployeeController::class, 'demands']);
$router->post('/employee/demands/complete', [EmployeeController::class, 'completeDemand']);
$router->post('/employee/demands/status', [EmployeeController::class, 'updateStatus']);
$router->get('/employee/notifications', [EmployeeController::class, 'notifications']);
$router->post('/employee/notifications/read', [EmployeeController::class, 'readNotification']);


// API de notificações (AJAX/polling) protegida por sessão
$router->get('/api/notifications/poll', [NotificationController::class, 'poll']);
$router->post('/api/notifications/read', [NotificationController::class, 'markReadAjax']);

// Download seguro de anexos
$router->get('/anexos/demandas/download', [AttachmentController::class, 'downloadDemandAttachment']);

// Sincronização parcial de painéis (polling seguro)
$router->get('/api/master/sync', [PanelSyncController::class, 'master']);
$router->get('/api/funcionario/sync', [PanelSyncController::class, 'employee']);

```
### Explicação técnica breve
Implementação compatível com as etapas anteriores, preservando autenticação, CSRF, regras de perfil e consistência entre banco/model/controller/view.
### Validação de compatibilidade
- Sintaxe PHP validada e integração mantida com rotas e views existentes.

## 9. Aplicar endurecimento de segurança
### Arquivos
- `app/Controllers/AttachmentController.php`
- `app/Services/SecureAttachmentService.php`
- `app/config/routes.php`
### Conteúdo completo dos arquivos
#### `app/Controllers/AttachmentController.php`
```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\SystemLog;
use App\Services\SecureAttachmentService;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly SecureAttachmentService $attachments = new SecureAttachmentService()
    ) {
    }

    public function downloadDemandAttachment(): void
    {
        $this->requireAuth();

        $demandId = (int)($_GET['demand_id'] ?? 0);
        if ($demandId <= 0) {
            http_response_code(400);
            echo 'Anexo inválido.';
            return;
        }

        $demand = $this->demands->findById($demandId);
        if (!$demand || empty($demand['anexo_emenda'])) {
            http_response_code(404);
            echo 'Anexo não encontrado.';
            return;
        }

        $role = (string)($_SESSION['user']['role'] ?? '');
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($role !== 'master' && (int)$demand['funcionario_id'] !== $userId) {
            http_response_code(403);
            echo 'Acesso negado ao anexo.';
            return;
        }

        $relativePath = (string)$demand['anexo_emenda'];
        $file = $this->attachments->resolveAbsolutePath($relativePath);
        if ($file === null) {
            http_response_code(404);
            echo 'Arquivo indisponível.';
            return;
        }

        $originalName = (string)($demand['anexo_nome_original'] ?? '');
        if ($originalName === '') {
            $originalName = $this->attachments->originalName($relativePath);
        }
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $inline = (($_GET['inline'] ?? '0') === '1') && $extension === 'pdf';

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($file);

        $this->logs->create([
            'usuario_id' => $userId,
            'acao' => 'download_anexo',
            'entidade' => 'demandas',
            'entidade_id' => $demandId,
            'descricao' => 'Download/visualização de anexo da demanda',
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . (string)filesize($file));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes($originalName) . '"');

        readfile($file);
        exit;
    }
}

```
#### `app/Services/SecureAttachmentService.php`
```php
<?php

namespace App\Services;

class SecureAttachmentService
{
    private const MAX_SIZE = 5242880; // 5MB
    private const BASE_DIR = 'storage/uploads/emendas';

    /**
     * @param array<string,mixed>|null $file
     * @return array{ok:bool,path:?string,original_name:?string,mime:?string,errors:array<int,string>}
     */
    public function store(?array $file): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => []];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha no upload do anexo.']];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_SIZE) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Arquivo inválido. Limite máximo: 5MB.']];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Upload não confiável.']];
        }

        $originalName = $this->sanitizeOriginalName((string)($file['name'] ?? 'documento'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'docx'], true)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Apenas arquivos PDF e DOCX são permitidos.']];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        if (!$this->isValidMime($extension, $mime)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Tipo MIME inválido para o arquivo enviado.']];
        }

        // Anti-mascaramento adicional por assinatura de arquivo.
        if (!$this->matchesBinarySignature($extension, $tmp)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Conteúdo do arquivo não corresponde ao tipo permitido.']];
        }

        $base = BASE_PATH . '/' . self::BASE_DIR;
        $metaDir = $base . '/.meta';

        if (!is_dir($base) && !mkdir($base, 0750, true) && !is_dir($base)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha ao preparar diretório de anexos.']];
        }
        if (!is_dir($metaDir) && !mkdir($metaDir, 0750, true) && !is_dir($metaDir)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha ao preparar diretório de metadados.']];
        }

        $this->ensureDenyDirectAccess($base);

        $secureName = 'anexo_' . date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $destination = $base . '/' . $secureName;
        if (!move_uploaded_file($tmp, $destination)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha ao salvar arquivo no servidor.']];
        }

        $relativePath = self::BASE_DIR . '/' . $secureName;

        $meta = [
            'original_name' => $originalName,
            'mime' => $mime,
            'uploaded_at' => date('c'),
        ];
        @file_put_contents($metaDir . '/' . $secureName . '.json', json_encode($meta, JSON_UNESCAPED_UNICODE));

        return ['ok' => true, 'path' => $relativePath, 'original_name' => $originalName, 'mime' => $mime, 'errors' => []];
    }

    public function resolveAbsolutePath(string $relativePath): ?string
    {
        $relativePath = ltrim($relativePath, '/');
        if (!str_starts_with($relativePath, self::BASE_DIR . '/')) {
            return null;
        }

        $absolute = BASE_PATH . '/' . $relativePath;
        if (!is_file($absolute)) {
            return null;
        }

        return $absolute;
    }

    public function originalName(string $relativePath): string
    {
        $fileName = basename($relativePath);
        $metaPath = BASE_PATH . '/' . self::BASE_DIR . '/.meta/' . $fileName . '.json';
        if (is_file($metaPath)) {
            $raw = @file_get_contents($metaPath);
            if (is_string($raw) && $raw !== '') {
                $json = json_decode($raw, true);
                if (is_array($json) && !empty($json['original_name'])) {
                    return (string)$json['original_name'];
                }
            }
        }

        return $fileName;
    }

    private function sanitizeOriginalName(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9._-]/', '_', trim($name)) ?? 'documento';
        return mb_substr($clean, 0, 150);
    }

    private function isValidMime(string $ext, string $mime): bool
    {
        if ($ext === 'pdf') {
            return $mime === 'application/pdf';
        }

        if ($ext === 'docx') {
            return in_array($mime, ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true);
        }

        return false;
    }

    private function matchesBinarySignature(string $ext, string $tmpPath): bool
    {
        $sample = @file_get_contents($tmpPath, false, null, 0, 8);
        if (!is_string($sample)) {
            return false;
        }

        if ($ext === 'pdf') {
            return str_starts_with($sample, "%PDF");
        }

        if ($ext === 'docx') {
            if (!str_starts_with($sample, "PK")) {
                return false;
            }

            $zip = new \ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                return false;
            }
            $hasManifest = $zip->locateName('[Content_Types].xml') !== false;
            $hasWordDoc = $zip->locateName('word/document.xml') !== false;
            $zip->close();
            return $hasManifest && $hasWordDoc;
        }

        return false;
    }

    private function ensureDenyDirectAccess(string $directory): void
    {
        $htaccess = $directory . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }
    }
}

```
#### `app/config/routes.php`
```php
<?php

use App\Controllers\AttachmentController;
use App\Controllers\AuthController;
use App\Controllers\EmployeeController;
use App\Controllers\FuncionarioController;
use App\Controllers\MasterController;
use App\Controllers\NotificationController;
use App\Controllers\PanelSyncController;

$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/change-password', [AuthController::class, 'showChangePassword']);
$router->post('/change-password', [AuthController::class, 'changePassword']);
$router->post('/logout', [AuthController::class, 'logout']);

// Área Master
$router->get('/master/dashboard', [MasterController::class, 'dashboard']);
$router->get('/master/employees', [MasterController::class, 'employees']);
$router->post('/master/employees/create', [MasterController::class, 'createEmployee']);
$router->post('/master/employees/update', [MasterController::class, 'updateEmployee']);
$router->post('/master/employees/password', [MasterController::class, 'changeEmployeePassword']);
$router->post('/master/employees/toggle', [MasterController::class, 'toggleEmployee']);
$router->post('/master/employees/delete', [MasterController::class, 'deleteEmployee']);
$router->get('/master/demands', [MasterController::class, 'demands']);
$router->post('/master/demands/create', [MasterController::class, 'createDemand']);
$router->post('/master/demands/update', [MasterController::class, 'updateDemand']);
$router->post('/master/demands/delete', [MasterController::class, 'deleteDemand']);
$router->get('/master/notifications', [MasterController::class, 'notifications']);
$router->post('/master/notifications/read', [MasterController::class, 'readNotification']);

// Área Funcionário (nomenclatura oficial)
$router->get('/funcionario/dashboard', [FuncionarioController::class, 'dashboard']);
$router->get('/funcionario/demandas', [FuncionarioController::class, 'demands']);
$router->post('/funcionario/demandas/concluir', [FuncionarioController::class, 'completeDemand']);
$router->post('/funcionario/demandas/status', [FuncionarioController::class, 'updateStatus']);
$router->get('/funcionario/notificacoes', [FuncionarioController::class, 'notifications']);
$router->post('/funcionario/notificacoes/lida', [FuncionarioController::class, 'readNotification']);

// Compatibilidade retroativa com rotas antigas /employee
$router->get('/employee/dashboard', [EmployeeController::class, 'dashboard']);
$router->get('/employee/demands', [EmployeeController::class, 'demands']);
$router->post('/employee/demands/complete', [EmployeeController::class, 'completeDemand']);
$router->post('/employee/demands/status', [EmployeeController::class, 'updateStatus']);
$router->get('/employee/notifications', [EmployeeController::class, 'notifications']);
$router->post('/employee/notifications/read', [EmployeeController::class, 'readNotification']);


// API de notificações (AJAX/polling) protegida por sessão
$router->get('/api/notifications/poll', [NotificationController::class, 'poll']);
$router->post('/api/notifications/read', [NotificationController::class, 'markReadAjax']);

// Download seguro de anexos
$router->get('/anexos/demandas/download', [AttachmentController::class, 'downloadDemandAttachment']);

// Sincronização parcial de painéis (polling seguro)
$router->get('/api/master/sync', [PanelSyncController::class, 'master']);
$router->get('/api/funcionario/sync', [PanelSyncController::class, 'employee']);

```
### Explicação técnica breve
Implementação compatível com as etapas anteriores, preservando autenticação, CSRF, regras de perfil e consistência entre banco/model/controller/view.
### Validação de compatibilidade
- Sintaxe PHP validada e integração mantida com rotas e views existentes.

## 10. Revisar o projeto inteiro e corrigir inconsistências
### Arquivos
- `app/views/layouts/header.php`
- `assets/js/app.js`
- `app/Controllers/MasterController.php`
### Conteúdo completo dos arquivos
#### `app/views/layouts/header.php`
```php
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<?php if (!empty($_SESSION['user'])): ?>
<?php
  $role = $_SESSION['user']['role'] ?? '';
  $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
  $basePath = rtrim(APP_BASE_PATH, '/');
  if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
      $requestPath = substr($requestPath, strlen($basePath));
  }
  $requestPath = '/' . ltrim($requestPath, '/');

  $navItems = $role === 'master'
    ? [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => '/master/dashboard'],
        ['label' => 'Funcionários', 'icon' => 'bi-people', 'path' => '/master/employees'],
        ['label' => 'Demandas', 'icon' => 'bi-list-task', 'path' => '/master/demands'],
        ['label' => 'Notificações', 'icon' => 'bi-bell', 'path' => '/master/notifications'],
      ]
    : [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => '/funcionario/dashboard'],
        ['label' => 'Minhas Demandas', 'icon' => 'bi-list-check', 'path' => '/funcionario/demandas'],
        ['label' => 'Notificações', 'icon' => 'bi-bell', 'path' => '/funcionario/notificacoes'],
      ];
?>
<div class="app-shell" id="appShell" data-polling-url="<?= url('api/notifications/poll') ?>" data-sync-url="<?= url($role === 'master' ? 'api/master/sync' : 'api/funcionario/sync') ?>" data-notification-read-url="<?= url('api/notifications/read') ?>" data-notification-base-url="<?= url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes') ?>" data-notification-latest-id="<?= (int)($headerNotifications[0]['id'] ?? 0) ?>" data-employee-status-url="<?= url('funcionario/demandas/status') ?>" data-master-demands-url="<?= url('master/demands') ?>" data-employee-demands-url="<?= url('funcionario/demandas') ?>" data-attachment-download-url="<?= url('anexos/demandas/download') ?>" data-csrf-token="<?= csrf_token() ?>">
  <aside class="sidebar" id="sidebarNav">
    <a class="brand" href="<?= url($role === 'master' ? 'master/dashboard' : 'funcionario/dashboard') ?>">
      <i class="bi bi-building"></i> Emendas Gov
    </a>

    <nav class="nav flex-column gap-1">
      <?php foreach ($navItems as $item): ?>
        <?php $active = str_starts_with($requestPath, $item['path']); ?>
        <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= url(ltrim($item['path'], '/')) ?>">
          <i class="bi <?= e($item['icon']) ?>"></i> <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="main-wrap">
    <header class="topbar d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm d-lg-none" id="sidebarToggle" type="button" aria-label="Abrir menu">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <small class="text-muted d-block text-uppercase fw-semibold">Painel institucional</small>
          <strong><?= e($_SESSION['user']['name']) ?></strong>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
          <button class="btn btn-outline-primary btn-sm position-relative" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <?php if ((int)$headerUnreadCount > 0): ?>
              <span id="headerUnreadBadge" class="badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle"><?= (int)$headerUnreadCount ?></span>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-0 shadow notification-menu">
            <div class="p-3 border-bottom">
              <strong>Notificações</strong>
            </div>
            <div class="notification-list" id="headerNotificationList">
              <?php if (!empty($headerNotifications)): ?>
                <?php foreach ($headerNotifications as $headerNotification): ?>
                  <a class="dropdown-item py-2" href="<?= url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes') ?>">
                    <div class="small fw-semibold"><?= e($headerNotification['titulo']) ?></div>
                    <div class="small text-muted"><?= e($headerNotification['mensagem']) ?></div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="px-3 py-3 text-muted small">Sem notificações recentes.</div>
              <?php endif; ?>
            </div>
            <a class="dropdown-item text-center py-2 border-top" href="<?= url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes') ?>">Ver todas</a>
          </div>
        </div>

        <form method="post" action="<?= url('logout') ?>" class="mb-0">
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
          <button class="btn btn-dark btn-sm"><i class="bi bi-box-arrow-right"></i> Sair</button>
        </form>
      </div>
    </header>

    <main class="container-fluid p-3 p-lg-4">
<?php else: ?>
<main class="container py-4">
<?php endif; ?>
<?php if ($error = flash('error')): ?><div class="alert alert-danger shadow-sm border-0"><?= e($error) ?></div><?php endif; ?>
<?php if ($success = flash('success')): ?><div class="alert alert-success shadow-sm border-0"><?= e($success) ?></div><?php endif; ?>

<div id="toastArea" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080"></div>

```
#### `assets/js/app.js`
```js
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initCalendar();
  initDataTable();
  initRealtimePanelSync();
});

function initSidebar() { /* unchanged */
  const appShell = document.getElementById('appShell');
  const sidebar = document.getElementById('sidebarNav');
  const sidebarToggle = document.getElementById('sidebarToggle');
  if (!appShell || !sidebar || !sidebarToggle) return;
  sidebarToggle.addEventListener('click', () => appShell.classList.toggle('sidebar-open'));
  document.addEventListener('click', (event) => {
    if (window.innerWidth > 991) return;
    if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) appShell.classList.remove('sidebar-open');
  });
}

function initCalendar() {
  const calendarEl = document.getElementById('calendar');
  if (!calendarEl || !window.FullCalendar) return;
  const events = JSON.parse(calendarEl.dataset.events || '[]');
  new FullCalendar.Calendar(calendarEl, { initialView: 'dayGridMonth', locale: 'pt-br', events, height: 'auto', dayMaxEvents: true, buttonText: { today: 'Hoje' } }).render();
}

function initDataTable() {
  if (!window.jQuery || !window.jQuery.fn.DataTable) return;
  window.jQuery('.data-table').DataTable({ pageLength: 10, order: [], responsive: true, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' } });
}

function initRealtimePanelSync() {
  const appShell = document.getElementById('appShell');
  if (!appShell) return;

  const syncUrl = appShell.dataset.syncUrl;
  const readUrl = appShell.dataset.notificationReadUrl;
  const statusUrl = appShell.dataset.employeeStatusUrl;
  const csrfToken = appShell.dataset.csrfToken || '';
  const fallbackListUrl = appShell.dataset.notificationBaseUrl || '#';
  const masterDemandsUrl = appShell.dataset.masterDemandsUrl || fallbackListUrl;
  const employeeDemandsUrl = appShell.dataset.employeeDemandsUrl || fallbackListUrl;
  const attachmentDownloadUrl = appShell.dataset.attachmentDownloadUrl || '';
  const toastArea = document.getElementById('toastArea');

  if (!syncUrl || !readUrl) return;

  let lastNotificationId = Number(appShell.dataset.notificationLatestId || 0);
  let nativeAllowed = false;

  if ('Notification' in window) {
    if (Notification.permission === 'granted') nativeAllowed = true;
    else if (Notification.permission === 'default') {
      Notification.requestPermission().then((p) => { nativeAllowed = p === 'granted'; }).catch(() => {});
    }
  }

  const poll = async () => {
    try {
      const response = await fetch(syncUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!response.ok) return;
      const payload = await response.json();
      if (!payload.ok) return;

      updateUnreadBadge(payload.unread_count || 0);
      renderHeaderNotifications(payload.notifications || [], fallbackListUrl);

      const newItems = (payload.notifications || []).filter((n) => Number(n.id || 0) > lastNotificationId);
      if (newItems.length) {
        lastNotificationId = Math.max(lastNotificationId, ...newItems.map((n) => Number(n.id || 0)));
        newItems.reverse().forEach((item) => {
          const destination = (item.referencia_tabela === 'demandas')
            ? (payload.role === 'master' ? masterDemandsUrl : employeeDemandsUrl)
            : fallbackListUrl;
          showToast(item, destination, toastArea, () => markNotificationRead(readUrl, csrfToken, Number(item.id || 0)));
          if (nativeAllowed) new Notification(item.titulo || 'Notificação', { body: item.mensagem || '' });
        });
      }

      if (payload.role === 'master') {
        renderMasterDemands(payload.demands || [], masterDemandsUrl, attachmentDownloadUrl);
        renderMasterStats(payload.stats || {});
        renderMasterUpcoming(payload.upcoming || []);
        renderMasterDashboardNotifications(payload.notifications || []);
      } else {
        renderEmployeeDemands(payload.demands || [], statusUrl, csrfToken, attachmentDownloadUrl);
        renderEmployeeSummary(payload.summary || {});
      }
    } catch (error) {
      console.warn('Falha no sync parcial:', error);
    }
  };

  poll();
  setInterval(poll, 15000);
}

function renderHeaderNotifications(items, fallbackUrl) {
  const box = document.getElementById('headerNotificationList');
  if (!box) return;
  if (!items.length) {
    box.innerHTML = '<div class="px-3 py-3 text-muted small">Sem notificações recentes.</div>';
    return;
  }
  box.innerHTML = items.map((n) => `
    <a class="dropdown-item py-2" href="${fallbackUrl}">
      <div class="small fw-semibold">${escapeHtml(n.titulo || 'Notificação')}</div>
      <div class="small text-muted">${escapeHtml(n.mensagem || '')}</div>
    </a>`).join('');
}

function renderMasterDemands(demands, masterDemandsUrl, attachmentDownloadUrl) {
  const tbody = document.getElementById('masterDemandsBody');
  if (!tbody) return;
  tbody.innerHTML = demands.map((d) => `
    <tr>
      <td><strong>${escapeHtml(d.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(d.funcionario_nome || '-')}</small></td>
      <td>${escapeHtml(d.funcionario_nome || '-')}</td>
      <td>${formatDate(d.data_prazo_resposta)}</td>
      <td><span class="badge bg-secondary">${escapeHtml(d.status || '')}</span></td>
      <td>${d.anexo_emenda ? `<a class=\"btn btn-sm btn-outline-dark\" href=\"${attachmentDownloadUrl}?inline=1&demand_id=${Number(d.id||0)}\" target=\"_blank\">Visualizar</a>` : '<span class=\"text-muted\">-</span>'}</td>
      <td><a class="btn btn-sm btn-outline-primary" href="${masterDemandsUrl}">Abrir painel</a></td>
    </tr>
  `).join('');
}

function renderEmployeeDemands(demands, statusUrl, csrf, attachmentDownloadUrl) {
  const tbody = document.getElementById('employeeDemandsBody');
  if (!tbody) return;
  tbody.innerHTML = demands.map((d) => `
    <tr>
      <td><strong>${escapeHtml(d.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(d.nome_politico || '')}</small></td>
      <td>${escapeHtml(d.tipo_processo || '')} / ${escapeHtml(d.tipo_emenda || '')}</td>
      <td>${formatDate(d.data_prazo_resposta)}</td>
      <td><span class="badge bg-secondary">${escapeHtml(d.status || '')}</span></td>
      <td>${d.anexo_emenda ? `<a target=\"_blank\" href=\"${attachmentDownloadUrl}?demand_id=${Number(d.id||0)}\">Abrir</a>` : '-'}</td>
      <td>
        <form method="post" action="${statusUrl || '/emendas/funcionario/demandas/status'}" class="d-flex gap-2">
          <input type="hidden" name="_csrf" value="${escapeHtml(csrf)}">
          <input type="hidden" name="id" value="${Number(d.id||0)}">
          <select class="form-select form-select-sm" name="status" required>
            <option value="pendente" ${d.status === 'pendente' ? 'selected' : ''}>Pendente</option>
            <option value="cadastrado" ${d.status === 'cadastrado' ? 'selected' : ''}>Cadastrado</option>
          </select>
          <button class="btn btn-sm btn-primary">Atualizar</button>
        </form>
      </td>
    </tr>
  `).join('');
}

function renderMasterStats(stats) {
  const map = [
    ['masterStatTotal', stats.total || 0],
    ['masterStatPendente', stats.pendente || 0],
    ['masterStatCadastrado', stats.cadastrado || 0],
  ];
  map.forEach(([id, val]) => { const el = document.getElementById(id); if (el) el.textContent = String(val); });
}

function renderEmployeeSummary(summary) {
  const map = [['employeeSummaryTotal', summary.total || 0], ['employeeSummaryPendente', summary.pendente || 0], ['employeeSummaryCadastrado', summary.cadastrado || 0]];
  map.forEach(([id, val]) => { const el = document.getElementById(id); if (el) el.textContent = String(val); });
}

function renderMasterUpcoming(items) {
  const ul = document.getElementById('masterUpcomingList');
  if (!ul) return;
  ul.innerHTML = items.length ? items.map((i) => `<li class="list-group-item px-0 d-flex justify-content-between align-items-start"><div><strong>${escapeHtml(i.emenda || '')}</strong><br><small class="text-muted">${escapeHtml(i.funcionario_nome || '')}</small></div><small class="badge bg-dark">${formatDate(i.data_prazo_resposta, true)}</small></li>`).join('') : '<li class="list-group-item px-0 text-muted">Nenhuma demanda com prazo crítico.</li>';
}

function renderMasterDashboardNotifications(items) {
  const ul = document.getElementById('masterDashboardNotifications');
  if (!ul) return;
  ul.innerHTML = items.length ? items.map((n) => `<li class="list-group-item px-0"><strong>${escapeHtml(n.titulo || '')}</strong><br><small class="text-muted">${escapeHtml(n.mensagem || '')}</small></li>`).join('') : '<li class="list-group-item px-0 text-muted">Sem notificações recentes.</li>';
}

async function markNotificationRead(readUrl, csrfToken, id) {
  if (!id) return;
  const body = new URLSearchParams(); body.set('_csrf', csrfToken); body.set('id', String(id));
  try {
    const response = await fetch(readUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: body.toString() });
    if (!response.ok) return;
    const payload = await response.json();
    if (payload.ok) updateUnreadBadge(payload.unread_count || 0);
  } catch (_) {}
}

function updateUnreadBadge(count) {
  const btn = document.querySelector('button[data-bs-toggle="dropdown"]'); if (!btn) return;
  let badge = document.getElementById('headerUnreadBadge');
  if (count <= 0) { if (badge) badge.remove(); return; }
  if (!badge) { badge = document.createElement('span'); badge.id = 'headerUnreadBadge'; badge.className = 'badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle'; btn.appendChild(badge); }
  badge.textContent = String(count);
}

function showToast(item, destinationUrl, toastArea, onOpen) {
  if (!toastArea || !window.bootstrap) return;
  const title = escapeHtml(item.titulo || 'Notificação');
  const message = escapeHtml(item.mensagem || 'Nova atualização disponível.');
  const timestamp = new Date(item.created_at || Date.now()).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  const wrapper = document.createElement('div');
  wrapper.innerHTML = `<div class="toast border-0 shadow-sm" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000"><div class="toast-header"><strong class="me-auto">${title}</strong><small>${timestamp}</small><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div><div class="toast-body"><a class="text-decoration-none" href="${destinationUrl}">${message}</a></div></div>`;
  const toastElement = wrapper.firstElementChild; toastArea.appendChild(toastElement);
  const anchor = toastElement.querySelector('a'); if (anchor && typeof onOpen === 'function') anchor.addEventListener('click', () => onOpen());
  new window.bootstrap.Toast(toastElement).show();
  toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
}

function formatDate(value, compact = false) {
  if (!value) return '-';
  const date = new Date(value.replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return escapeHtml(value);
  return date.toLocaleString('pt-BR', compact ? { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' } : { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(value) {
  return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

```
#### `app/Controllers/MasterController.php`
```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\NotificationDeadlineService;
use App\Services\SecureAttachmentService;

class MasterController extends Controller
{
    public function __construct(
        private readonly User $users = new User(),
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly NotificationDeadlineService $deadlineService = new NotificationDeadlineService(),
        private readonly SecureAttachmentService $attachmentService = new SecureAttachmentService()
    ) {
    }

    public function dashboard(): void
    {
        $this->requireAuth('master');
        $masterId = (int)$_SESSION['user']['id'];
        $this->deadlineService->runAutomationForMaster($masterId);

        $this->view('master/dashboard/index', [
            'stats' => $this->demands->stats(),
            'demands' => $this->demands->paginatedFiltered([], 300, 0),
            'notifications' => $this->notifications->forUser($masterId, 8),
            'upcoming' => $this->demands->upcomingDeadlines(8),
        ]);
    }

    public function employees(): void { /* unchanged */
        $this->requireAuth('master');
        $search = trim((string)($_GET['search'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 8;
        $offset = ($page - 1) * $perPage;
        $total = $this->users->countEmployees($search !== '' ? $search : null);
        $employees = $this->users->paginatedEmployees($perPage, $offset, $search !== '' ? $search : null);
        $this->view('master/employees/index', ['employees' => $employees,'search' => $search,'page' => $page,'totalPages' => max(1, (int)ceil($total / $perPage))]);
    }

    public function createEmployee(): void { /* unchanged */
        $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$payload = $this->validateEmployeePayload();if (!$payload['ok']) {flash('error', implode(' ', $payload['errors']));redirect('master/employees');}
        $data = $payload['data'];if ($this->users->emailExists($data['email'])) {flash('error', 'E-mail já cadastrado.');redirect('master/employees');}
        if ($this->users->decreeExists($data['numero_decreto'])) {flash('error', 'Número de decreto já cadastrado.');redirect('master/employees');}
        $this->users->createEmployee(['nome_completo'=>$data['nome_completo'],'email'=>$data['email'],'endereco'=>$data['endereco'],'whatsapp'=>$data['whatsapp'],'numero_decreto'=>$data['numero_decreto'],'data_nascimento'=>$data['data_nascimento'],'usuario'=>$data['numero_decreto'],'senha_hash'=>password_hash(normalize_birth_password($data['data_nascimento']), PASSWORD_DEFAULT)]);
        $this->logAction('create', 'usuarios', null, 'Cadastro de funcionário realizado pelo Master');flash('success', 'Funcionário cadastrado com sucesso.');redirect('master/employees');
    }
    public function updateEmployee(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}$payload=$this->validateEmployeePayload();if(!$payload['ok']){flash('error',implode(' ',$payload['errors']));redirect('master/employees');}$data=$payload['data'];if($this->users->emailExists($data['email'],$id)||$this->users->decreeExists($data['numero_decreto'],$id)){flash('error','E-mail ou decreto já em uso.');redirect('master/employees');}$this->users->updateEmployee($id,$data);$this->logAction('update','usuarios',$id,'Dados do funcionário atualizados pelo Master');flash('success','Funcionário atualizado com sucesso.');redirect('master/employees'); }
    public function changeEmployeePassword(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);$newPassword=(string)($_POST['new_password']??'');if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}if(strlen($newPassword)<8){flash('error','Informe uma nova senha com no mínimo 8 caracteres.');redirect('master/employees');}$this->users->updatePassword($id,password_hash($newPassword,PASSWORD_DEFAULT),true);$this->logAction('update_password','usuarios',$id,'Master alterou senha de funcionário e reativou primeiro login');flash('success','Senha do funcionário alterada com sucesso.');redirect('master/employees'); }
    public function toggleEmployee(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}$active=(int)($_POST['active']??0)===1;$this->users->toggleEmployeeStatus($id,$active);$this->logAction('toggle','usuarios',$id,$active?'Funcionário ativado':'Funcionário desativado');flash('success',$active?'Funcionário ativado.':'Funcionário desativado.');redirect('master/employees'); }
    public function deleteEmployee(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}$this->users->toggleEmployeeStatus($id,false);$this->logAction('soft_delete','usuarios',$id,'Funcionário desativado por ação de exclusão lógica');flash('success','Funcionário desativado com sucesso.');redirect('master/employees'); }

    public function demands(): void
    {
        $this->requireAuth('master');
        $this->deadlineService->runAutomationForMaster((int)$_SESSION['user']['id']);
        $filters = ['status'=>trim((string)($_GET['status'] ?? '')),'funcionario_id'=>trim((string)($_GET['funcionario_id'] ?? '')),'prazo_de'=>trim((string)($_GET['prazo_de'] ?? '')),'prazo_ate'=>trim((string)($_GET['prazo_ate'] ?? ''))];
        $page = max(1, (int)($_GET['page'] ?? 1));$perPage = 10;$offset = ($page - 1) * $perPage;$total = $this->demands->countFiltered($filters);
        $this->view('master/demands/index', ['demands'=>$this->demands->paginatedFiltered($filters,$perPage,$offset),'employees'=>$this->users->allActiveEmployees(),'filters'=>$filters,'page'=>$page,'totalPages'=>max(1,(int)ceil($total/$perPage))]);
    }

    public function createDemand(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/demands');

        $payload = $this->validateDemandPayload();
        if (!$payload['ok']) { flash('error', implode(' ', $payload['errors'])); redirect('master/demands'); }

        $dataToPersist = $payload['data'];
        $uploadOriginalName = $dataToPersist['_upload_original_name'] ?? null;
        unset($dataToPersist['_upload_original_name']);

        $demandId = $this->demands->create($dataToPersist + ['criado_por' => (int)$_SESSION['user']['id']]);
        $demand = $this->demands->findById($demandId) ?? ($payload['data'] + ['id' => $demandId]);

        $masterId = (int)$_SESSION['user']['id'];
        $this->deadlineService->notifyDemandEventToMaster($masterId, $demand, 'Nova demanda cadastrada', 'A demanda foi cadastrada com sucesso no sistema.', 'demanda_criada_master');
        $this->deadlineService->notifyDemandAssignedToEmployee((int)$payload['data']['funcionario_id'], $demand);

        $this->logAction('create', 'demandas', $demandId, 'Demanda cadastrada pelo Master');
        if (is_string($uploadOriginalName) && $uploadOriginalName !== '') {
            $this->logAction('upload_anexo', 'demandas', $demandId, 'Upload do anexo: ' . $uploadOriginalName);
        }
        flash('success', 'Demanda cadastrada com sucesso.');
        redirect('master/demands');
    }

    public function updateDemand(): void
    {
        $this->requireAuth('master');$this->assertCsrfOrRedirect('master/demands');
        $id = (int)($_POST['id'] ?? 0); if ($id <= 0) { flash('error', 'Demanda inválida.'); redirect('master/demands'); }
        $current = $this->demands->findById($id); if (!$current) { flash('error', 'Demanda não encontrada.'); redirect('master/demands'); }
        $payload = $this->validateDemandPayload($current['anexo_emenda'] ?? null);
        if (!$payload['ok']) { flash('error', implode(' ', $payload['errors'])); redirect('master/demands'); }
        $dataToPersist = $payload['data'];
        $uploadOriginalName = $dataToPersist['_upload_original_name'] ?? null;
        unset($dataToPersist['_upload_original_name']);

        $this->demands->update($id, $dataToPersist);
        $updated = $this->demands->findById($id) ?? ($payload['data'] + ['id' => $id]);
        $this->deadlineService->notifyDemandEventToMaster((int)$_SESSION['user']['id'], $updated, 'Demanda atualizada', 'Uma demanda foi atualizada no painel Master.', 'demanda_atualizada_master');
        $this->deadlineService->notifyDemandUpdatedToEmployee((int)$payload['data']['funcionario_id'], $updated);
        $this->logAction('update', 'demandas', $id, 'Demanda atualizada pelo Master');
        if (is_string($uploadOriginalName) && $uploadOriginalName !== '') {
            $this->logAction('upload_anexo', 'demandas', $id, 'Substituição de anexo: ' . $uploadOriginalName);
        }
        flash('success', 'Demanda atualizada com sucesso.');
        redirect('master/demands');
    }

    public function deleteDemand(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/demands');$id=(int)($_POST['id']??0);if($id<=0){flash('error','Demanda inválida.');redirect('master/demands');}$this->demands->delete($id);$this->logAction('delete','demandas',$id,'Demanda removida pelo Master');flash('success','Demanda removida.');redirect('master/demands'); }
    public function readNotification(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/notifications');$notificationId=(int)($_POST['id']??0);$this->notifications->markRead($notificationId,(int)$_SESSION['user']['id']);flash('success','Notificação marcada como lida.');redirect('master/notifications'); }
    public function notifications(): void { $this->requireAuth('master');$masterId=(int)$_SESSION['user']['id'];$this->deadlineService->runAutomationForMaster($masterId);$this->view('master/notifications/index',['notifications'=>$this->notifications->forUser($masterId,50)]); }

    public function polling(): void
    {
        $this->requireAuth('master');
        $this->deadlineService->runAutomationForMaster((int)$_SESSION['user']['id']);
        $this->jsonPollingResponse();
    }

    private function validateEmployeePayload(): array
    {
        $data=['nome_completo'=>trim((string)($_POST['nome_completo']??'')),'email'=>trim((string)($_POST['email']??'')),'endereco'=>trim((string)($_POST['endereco']??'')),'whatsapp'=>trim((string)($_POST['whatsapp']??'')),'numero_decreto'=>trim((string)($_POST['numero_decreto']??'')),'data_nascimento'=>trim((string)($_POST['data_nascimento']??''))];
        $errors=validate_required($data,['nome_completo','email','endereco','whatsapp','numero_decreto','data_nascimento']);
        if($data['email']!==''&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL)){$errors[]='E-mail inválido.';}
        if($data['data_nascimento']!==''&&strtotime($data['data_nascimento'])===false){$errors[]='Data de nascimento inválida.';}
        return ['ok'=>$errors===[],'data'=>$data,'errors'=>$errors];
    }

    private function validateDemandPayload(?string $existingAttachment = null): array
    {
        $data = [
            'emenda' => trim((string)($_POST['emenda'] ?? '')),
            'nome_politico' => trim((string)($_POST['nome_politico'] ?? '')),
            'tipo_processo' => trim((string)($_POST['tipo_processo'] ?? '')),
            'tipo_emenda' => trim((string)($_POST['tipo_emenda'] ?? '')),
            'data_prazo_resposta' => trim((string)($_POST['data_prazo_resposta'] ?? '')),
            'data_cadastro_emenda' => trim((string)($_POST['data_cadastro_emenda'] ?? '')),
            'observacao' => trim((string)($_POST['observacao'] ?? '')),
            'funcionario_id' => (int)($_POST['funcionario_id'] ?? 0),
            'status' => trim((string)($_POST['status'] ?? 'pendente')),
            'anexo_emenda' => $existingAttachment,
            'anexo_nome_original' => trim((string)($_POST['anexo_nome_original'] ?? '')),
        ];

        $errors = validate_required($data, ['emenda', 'nome_politico', 'tipo_processo', 'tipo_emenda', 'data_prazo_resposta', 'data_cadastro_emenda']);
        if (!in_array($data['tipo_processo'], Demand::PROCESS_TYPES, true)) { $errors[] = 'Tipo de processo inválido.'; }
        if (!in_array($data['tipo_emenda'], Demand::AMENDMENT_TYPES, true)) { $errors[] = 'Tipo de emenda inválido.'; }
        if (!in_array($data['status'], Demand::STATUS_ALLOWED, true)) { $errors[] = 'Status da demanda inválido.'; }
        if ($data['funcionario_id'] <= 0 || !$this->users->existsEmployeeById($data['funcionario_id'])) { $errors[] = 'Selecione um funcionário válido para a demanda.'; }

        $prazoTimestamp = strtotime($data['data_prazo_resposta']);
        $cadastroTimestamp = strtotime($data['data_cadastro_emenda']);
        if ($prazoTimestamp === false) { $errors[] = 'Data prazo de resposta inválida.'; } else { $data['data_prazo_resposta'] = date('Y-m-d H:i:s', $prazoTimestamp); }
        if ($cadastroTimestamp === false) { $errors[] = 'Data de cadastro da emenda inválida.'; } else { $data['data_cadastro_emenda'] = date('Y-m-d', $cadastroTimestamp); }

        $upload = $this->attachmentService->store($_FILES['anexo_emenda'] ?? null);
        if (!$upload['ok']) {
            $errors = array_merge($errors, $upload['errors']);
        } elseif ($upload['path'] !== null) {
            $data['anexo_emenda'] = $upload['path'];
            $data['_upload_original_name'] = $upload['original_name'];
            $data['anexo_nome_original'] = (string)$upload['original_name'];
        }

        return ['ok' => $errors === [], 'data' => $data, 'errors' => $errors];
    }

    private function jsonPollingResponse(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $userId = (int)$_SESSION['user']['id'];
        $afterId = max(0, (int)($_GET['after_id'] ?? 0));
        $items = $this->notifications->latestForUser($userId, $afterId, 20);
        echo json_encode(['ok' => true,'unread_count' => $this->notifications->unreadCount($userId),'items' => $items,'latest_id' => $this->notifications->latestIdForUser($userId)]);
        exit;
    }

    private function assertCsrfOrRedirect(string $redirectPath): void
    {
        if (!verify_csrf($_POST['_csrf'] ?? null)) { flash('error', 'Falha de segurança na requisição.'); redirect($redirectPath); }
    }

    private function logAction(string $acao, string $entidade, ?int $entidadeId, string $descricao): void
    {
        $this->logs->create(['usuario_id'=>(int)($_SESSION['user']['id'] ?? 0) ?: null,'acao'=>$acao,'entidade'=>$entidade,'entidade_id'=>$entidadeId,'descricao'=>$descricao,'ip'=>client_ip(),'user_agent'=>$_SERVER['HTTP_USER_AGENT'] ?? null]);
    }
}

```
### Explicação técnica breve
Implementação compatível com as etapas anteriores, preservando autenticação, CSRF, regras de perfil e consistência entre banco/model/controller/view.
### Validação de compatibilidade
- Sintaxe PHP validada e integração mantida com rotas e views existentes.

