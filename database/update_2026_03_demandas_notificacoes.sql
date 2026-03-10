ALTER TABLE demandas
  ADD COLUMN tipo_processo ENUM('prestacao_de_conta','cadastro_de_emenda') NOT NULL AFTER nome_politico,
  MODIFY COLUMN tipo_emenda ENUM('parlamentar','estadual','municipal') NOT NULL,
  ADD COLUMN data_prazo_resposta DATETIME NULL AFTER tipo_emenda,
  ADD COLUMN data_cadastro_emenda DATE NULL AFTER data_prazo_resposta,
  ADD COLUMN anexo_emenda VARCHAR(255) NULL AFTER observacao,
  ADD COLUMN data_ultima_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER criado_por,
  MODIFY COLUMN status ENUM('pendente','cadastrado') NOT NULL DEFAULT 'pendente';

UPDATE demandas
SET data_prazo_resposta = COALESCE(data_prazo_resposta, prazo_entrega),
    data_cadastro_emenda = COALESCE(data_cadastro_emenda, data_emenda),
    data_ultima_atualizacao = COALESCE(data_ultima_atualizacao, updated_at),
    tipo_processo = COALESCE(tipo_processo, 'cadastro_de_emenda');

ALTER TABLE demandas
  MODIFY COLUMN data_prazo_resposta DATETIME NOT NULL,
  MODIFY COLUMN data_cadastro_emenda DATE NOT NULL,
  DROP COLUMN prazo_entrega,
  DROP COLUMN data_emenda,
  DROP COLUMN data_conclusao,
  DROP COLUMN observacao_conclusao;

CREATE INDEX idx_demandas_func_status_prazo ON demandas (funcionario_id, status, data_prazo_resposta);
