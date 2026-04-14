-- =====================================================================
-- Migração segura (HostGator/phpMyAdmin) - Gestão de Emendas
-- Banco alvo: santo821_emenda
-- Objetivo:
--   1) Atualizar estrutura de demandas para o novo fluxo
--   2) Restringir status a ('pendente','cadastrada','concluído')
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

    -- Tabela opcional recomendada para auditoria de sessões.
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'sessoes_usuario';

    IF v_exists = 0 THEN
        CREATE TABLE sessoes_usuario (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id INT UNSIGNED NOT NULL,
            session_id VARCHAR(128) NOT NULL,
            ip VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            data_login DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ultima_atividade DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uq_sessoes_usuario_session_id (session_id),
            KEY idx_sessoes_usuario_usuario_ativo (usuario_id, ativo),
            KEY idx_sessoes_usuario_atividade (ultima_atividade),
            CONSTRAINT fk_sessoes_usuario FOREIGN KEY (usuario_id)
                REFERENCES usuarios(id)
                ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;
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


    SELECT COUNT(*) INTO v_exists
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas_historico';

    IF v_exists = 0 THEN
        CREATE TABLE demandas_historico (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            demanda_id BIGINT UNSIGNED NOT NULL,
            usuario_id INT UNSIGNED NULL,
            usuario_nome VARCHAR(150) NOT NULL,
            acao VARCHAR(50) NOT NULL DEFAULT 'status_change',
            status_anterior VARCHAR(40) NULL,
            status_novo VARCHAR(40) NOT NULL,
            observacao TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_hist_demanda_created (demanda_id, created_at),
            KEY idx_hist_usuario (usuario_id),
            CONSTRAINT fk_hist_demanda FOREIGN KEY (demanda_id) REFERENCES demandas(id) ON DELETE CASCADE,
            CONSTRAINT fk_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;

    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas_historico'
       AND COLUMN_NAME = 'acao';

    IF v_exists = 0 THEN
        ALTER TABLE demandas_historico
          ADD COLUMN acao VARCHAR(50) NOT NULL DEFAULT 'status_change' AFTER usuario_nome;
    END IF;

    -- -----------------------------------------------------------------
    -- 1) DEMANDAS: NOVOS CAMPOS E AJUSTES
    -- -----------------------------------------------------------------

    -- 1.0 numero_processo_sei
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'numero_processo_sei';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN numero_processo_sei VARCHAR(80) NULL AFTER nome_politico;
    END IF;

    -- 1.1 tipo_processo
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'tipo_processo';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN tipo_processo ENUM('prestacao_de_conta','cadastro_de_emenda','demanda_administrativa','outros') NULL AFTER nome_politico;
    END IF;

    -- 1.1.1 tipo_processo_outros
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'tipo_processo_outros';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN tipo_processo_outros VARCHAR(255) NULL AFTER tipo_processo;
    END IF;

    -- garante enum atualizado com novas opções
    ALTER TABLE demandas
      MODIFY COLUMN tipo_processo ENUM('prestacao_de_conta','cadastro_de_emenda','demanda_administrativa','outros') NULL;

    -- 1.2 tipo_emenda (normalização para ENUM do novo domínio)
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'tipo_emenda';

    IF v_exists = 0 THEN
        ALTER TABLE demandas
          ADD COLUMN tipo_emenda ENUM('federal','estadual','municipal') NULL AFTER tipo_processo;
    ELSE
        -- Mantém dados existentes e converte tipo
        ALTER TABLE demandas
          MODIFY COLUMN tipo_emenda ENUM('federal','estadual','municipal') NULL;
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


    -- 1.5.1 observacao_funcionario
    SELECT COUNT(*) INTO v_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'demandas'
       AND COLUMN_NAME = 'observacao_funcionario';

    IF v_exists = 0 THEN
      ALTER TABLE demandas
          ADD COLUMN observacao_funcionario TEXT NULL AFTER observacao;
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
    -- Migra valor legado 'parlamentar' para 'federal'
    UPDATE demandas
       SET tipo_emenda = 'federal'
     WHERE tipo_emenda = 'parlamentar';

    UPDATE demandas
       SET tipo_emenda = 'federal'
     WHERE tipo_emenda IS NULL
        OR tipo_emenda NOT IN ('federal','estadual','municipal','parlamentar');

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
      MODIFY COLUMN nome_politico VARCHAR(150) NULL,
      MODIFY COLUMN tipo_processo ENUM('prestacao_de_conta','cadastro_de_emenda','demanda_administrativa','outros') NOT NULL,
      MODIFY COLUMN tipo_emenda ENUM('federal','estadual','municipal') NOT NULL,
      MODIFY COLUMN data_prazo_resposta DATETIME NOT NULL,
      MODIFY COLUMN data_cadastro_emenda DATE NOT NULL;

    -- -----------------------------------------------------------------
    -- 3) STATUS: RESTRIÇÃO PARA ('pendente','cadastrada','concluído')
    -- -----------------------------------------------------------------

    -- Mapeia status legado para domínio novo
    UPDATE demandas
       SET status = CASE
           WHEN status IN ('cadastrada') THEN 'cadastrada'
           WHEN status IN ('em_andamento') THEN 'cadastrada'
           WHEN status IN ('concluida','concluído') THEN 'concluído'
           WHEN status IN ('atrasada') THEN 'pendente'
           ELSE 'pendente'
       END;

    ALTER TABLE demandas
      MODIFY COLUMN status ENUM('pendente','cadastrada','concluído') NOT NULL DEFAULT 'pendente';

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
