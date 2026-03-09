<?php

namespace App\Models;

use App\Core\Database;

/**
 * Registro de ações sensíveis do sistema (auditoria).
 */
class SystemLog
{
    public function create(array $data): bool
    {
        $sql = 'INSERT INTO logs_sistema (usuario_id, acao, entidade, entidade_id, descricao, ip, user_agent)
                VALUES (:usuario_id, :acao, :entidade, :entidade_id, :descricao, :ip, :user_agent)';

        $stmt = Database::connection()->prepare($sql);

        return $stmt->execute([
            'usuario_id' => $data['usuario_id'] ?? null,
            'acao' => $data['acao'] ?? 'acao_desconhecida',
            'entidade' => $data['entidade'] ?? 'sessao',
            'entidade_id' => $data['entidade_id'] ?? null,
            'descricao' => $data['descricao'] ?? null,
            'ip' => $data['ip'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }
}
