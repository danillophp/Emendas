<?php

namespace App\Models;

use App\Core\Database;

class Notification
{
    /**
     * Função reaproveitável para registrar notificações internas.
     */
    public function notify(
        int $usuarioId,
        string $titulo,
        string $mensagem,
        string $tipo,
        ?int $referenciaId = null,
        ?string $referenciaTabela = null
    ): bool {
        $sql = 'INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, referencia_id, referencia_tabela)
                VALUES (:usuario_id, :titulo, :mensagem, :tipo, :referencia_id, :referencia_tabela)';

        return Database::connection()->prepare($sql)->execute([
            'usuario_id' => $usuarioId,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'tipo' => $tipo,
            'referencia_id' => $referenciaId,
            'referencia_tabela' => $referenciaTabela,
        ]);
    }

    public function create(array $data): bool
    {
        return $this->notify(
            (int)$data['usuario_id'],
            (string)$data['titulo'],
            (string)$data['mensagem'],
            (string)$data['tipo'],
            isset($data['referencia_id']) ? (int)$data['referencia_id'] : null,
            $data['referencia_tabela'] ?? null
        );
    }

    /**
     * Verifica se já existe notificação recente para evitar duplicidade excessiva.
     */
    public function existsRecent(int $usuarioId, string $type, int $referenciaId, int $hoursWindow = 24): bool
    {
        $cutoff = date('Y-m-d H:i:s', time() - max(1, $hoursWindow) * 3600);

        $sql = 'SELECT id FROM notificacoes
                WHERE usuario_id = :usuario_id AND tipo = :tipo AND referencia_id = :referencia_id
                AND created_at >= :cutoff
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, \PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $type);
        $stmt->bindValue(':referencia_id', $referenciaId, \PDO::PARAM_INT);
        $stmt->bindValue(':cutoff', $cutoff);
        $stmt->execute();
        return (bool)$stmt->fetch();
    }

    public function forUser(int $userId, int $limit = 30): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM notificacoes WHERE usuario_id = :usuario_id ORDER BY created_at DESC LIMIT :limite');
        $stmt->bindValue(':usuario_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    public function latestForUser(int $userId, int $afterId = 0, int $limit = 10): array
    {
        $sql = 'SELECT * FROM notificacoes WHERE usuario_id = :usuario_id AND id > :after_id ORDER BY id ASC LIMIT :limite';
        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue(':usuario_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':after_id', $afterId, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function latestIdForUser(int $userId): int
    {
        $stmt = Database::connection()->prepare('SELECT MAX(id) AS max_id FROM notificacoes WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $userId]);
        $row = $stmt->fetch();
        return (int)($row['max_id'] ?? 0);
    }

    public function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) AS total FROM notificacoes WHERE usuario_id = :usuario_id AND lida = 0');
        $stmt->execute(['usuario_id' => $userId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function markAllRead(int $userId): bool
    {
        return Database::connection()->prepare('UPDATE notificacoes SET lida = 1 WHERE usuario_id = :usuario_id')->execute(['usuario_id' => $userId]);
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        $sql = 'UPDATE notificacoes SET lida = 1 WHERE id = :id AND usuario_id = :usuario_id';
        return Database::connection()->prepare($sql)->execute(['id' => $notificationId, 'usuario_id' => $userId]);
    }
}
