-- =========================================================
-- Sistema de Gestão de Emendas Governamentais
-- Banco: santo821_emenda
-- Compatível com MySQL / phpMyAdmin (HostGator)
-- =========================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `santo821_emenda`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `santo821_emenda`;

-- ---------------------------------------------------------
-- Reimportação segura (ordem por dependência)
-- ---------------------------------------------------------
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
  `data_emenda` DATE NOT NULL,
  `tipo_emenda` VARCHAR(100) NOT NULL,
  `observacao` TEXT NULL,
  `prazo_entrega` DATETIME NOT NULL,
  `funcionario_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pendente','em_andamento','concluida','atrasada') NOT NULL DEFAULT 'pendente',
  `criado_por` INT UNSIGNED NOT NULL,
  `data_conclusao` DATETIME NULL,
  `observacao_conclusao` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_demandas_status` (`status`),
  KEY `idx_demandas_prazo_entrega` (`prazo_entrega`),
  KEY `idx_demandas_funcionario_id` (`funcionario_id`),
  KEY `idx_demandas_criado_por` (`criado_por`),
  KEY `idx_demandas_status_prazo` (`status`, `prazo_entrega`),
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
-- Usuário MASTER inicial
-- senha (hash bcrypt de exemplo): Master@123
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
