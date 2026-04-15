<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de usuários para autenticação e gestão administrativa.
 */
class User
{
    private static ?bool $sessionAuditTableExists = null;

    public function findByUsername(string $username): ?array
    {
        $sql = 'SELECT id, nome_completo, usuario, senha_hash, perfil, primeiro_login, ativo, session_token
                FROM usuarios
                WHERE usuario = :usuario
                LIMIT 1';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['usuario' => $username]);

        return $stmt->fetch() ?: null;
    }

    public function updatePassword(int $id, string $passwordHash, bool $firstLogin = false): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE usuarios SET senha_hash = :senha_hash, primeiro_login = :primeiro_login WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'senha_hash' => $passwordHash,
            'primeiro_login' => $firstLogin ? 1 : 0,
        ]);
    }

    public function updateSessionToken(int $id, ?string $token): bool
    {
        $stmt = Database::connection()->prepare('UPDATE usuarios SET session_token = :session_token WHERE id = :id');
        return $stmt->execute(['id' => $id, 'session_token' => $token]);
    }

    public function getSessionToken(int $id): ?string
    {
        $stmt = Database::connection()->prepare('SELECT session_token FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result['session_token'] ?? null;
    }

    public function supportsSessionAudit(): bool
    {
        if (self::$sessionAuditTableExists !== null) {
            return self::$sessionAuditTableExists;
        }

        $sql = 'SELECT COUNT(*) AS total
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "sessoes_usuario"';
        $row = Database::connection()->query($sql)->fetch();
        self::$sessionAuditTableExists = ((int)($row['total'] ?? 0) > 0);

        return self::$sessionAuditTableExists;
    }

    public function registerSession(int $userId, string $sessionId, ?string $ip, ?string $userAgent): bool
    {
        if (!$this->supportsSessionAudit()) {
            return false;
        }

        $sql = 'INSERT INTO sessoes_usuario (usuario_id, session_id, ip, user_agent, data_login, ultima_atividade, ativo)
                VALUES (:usuario_id, :session_id, :ip, :user_agent, NOW(), NOW(), 1)
                ON DUPLICATE KEY UPDATE ip = VALUES(ip), user_agent = VALUES(user_agent), ultima_atividade = NOW(), ativo = 1';
        $stmt = Database::connection()->prepare($sql);

        return $stmt->execute([
            'usuario_id' => $userId,
            'session_id' => $sessionId,
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    public function deactivateSessionsExcept(int $userId, string $sessionId): bool
    {
        if (!$this->supportsSessionAudit()) {
            return false;
        }

        $sql = 'UPDATE sessoes_usuario SET ativo = 0, ultima_atividade = NOW()
                WHERE usuario_id = :usuario_id AND session_id != :session_id AND ativo = 1';
        $stmt = Database::connection()->prepare($sql);

        return $stmt->execute([
            'usuario_id' => $userId,
            'session_id' => $sessionId,
        ]);
    }

    public function deactivateSession(int $userId, string $sessionId): bool
    {
        if (!$this->supportsSessionAudit()) {
            return false;
        }

        $sql = 'UPDATE sessoes_usuario
                SET ativo = 0, ultima_atividade = NOW()
                WHERE usuario_id = :usuario_id AND session_id = :session_id';
        $stmt = Database::connection()->prepare($sql);

        return $stmt->execute([
            'usuario_id' => $userId,
            'session_id' => $sessionId,
        ]);
    }

    public function isSessionActive(int $userId, string $sessionId): bool
    {
        if (!$this->supportsSessionAudit()) {
            return false;
        }

        $sql = 'SELECT id FROM sessoes_usuario
                WHERE usuario_id = :usuario_id AND session_id = :session_id AND ativo = 1
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'usuario_id' => $userId,
            'session_id' => $sessionId,
        ]);

        return (bool)$stmt->fetch();
    }

    public function touchSession(int $userId, string $sessionId): bool
    {
        if (!$this->supportsSessionAudit()) {
            return false;
        }

        $sql = 'UPDATE sessoes_usuario SET ultima_atividade = NOW()
                WHERE usuario_id = :usuario_id AND session_id = :session_id AND ativo = 1';
        $stmt = Database::connection()->prepare($sql);

        return $stmt->execute([
            'usuario_id' => $userId,
            'session_id' => $sessionId,
        ]);
    }


    public function activeSessions(?int $userId = null, int $limit = 200): array
    {
        if (!$this->supportsSessionAudit()) {
            return [];
        }

        $sql = 'SELECT su.id, su.usuario_id, su.session_id, su.ip, su.user_agent, su.data_login, su.ultima_atividade, su.ativo,
                       u.nome_completo, u.usuario, u.perfil
                FROM sessoes_usuario su
                JOIN usuarios u ON u.id = su.usuario_id
                WHERE su.ativo = 1';

        $params = [];
        if ($userId !== null) {
            $sql .= ' AND su.usuario_id = :usuario_id';
            $params['usuario_id'] = $userId;
        }

        $sql .= ' ORDER BY su.ultima_atividade DESC LIMIT :limite';

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':limite', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findActiveSessionById(int $sessionAuditId): ?array
    {
        if (!$this->supportsSessionAudit()) {
            return null;
        }

        $sql = 'SELECT id, usuario_id, session_id, ativo FROM sessoes_usuario WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $sessionAuditId]);

        return $stmt->fetch() ?: null;
    }

    public function deactivateSessionByAuditId(int $sessionAuditId): bool
    {
        if (!$this->supportsSessionAudit()) {
            return false;
        }

        $sql = 'UPDATE sessoes_usuario SET ativo = 0, ultima_atividade = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);

        return $stmt->execute(['id' => $sessionAuditId]);
    }

    public function countEmployees(?string $search = null): int
    {
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE perfil = 'funcionario'";
        $params = [];
        if ($search) {
            $sql .= ' AND (nome_completo LIKE :search OR email LIKE :search OR numero_decreto LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function paginatedEmployees(int $limit, int $offset, ?string $search = null): array
    {
        $sql = "SELECT id, nome_completo, email, endereco, whatsapp, numero_decreto, data_nascimento, ativo, created_at
                FROM usuarios
                WHERE perfil = 'funcionario'";
        $params = [];
        if ($search) {
            $sql .= ' AND (nome_completo LIKE :search OR email LIKE :search OR numero_decreto LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY nome_completo ASC LIMIT :limit OFFSET :offset';

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createEmployee(array $data): bool
    {
        $sql = 'INSERT INTO usuarios (nome_completo, email, endereco, whatsapp, numero_decreto, data_nascimento, usuario, senha_hash, perfil, primeiro_login, ativo)
                VALUES (:nome_completo, :email, :endereco, :whatsapp, :numero_decreto, :data_nascimento, :usuario, :senha_hash, "funcionario", 1, 1)';
        $stmt = Database::connection()->prepare($sql);
        return $stmt->execute($data);
    }

    public function updateEmployee(int $id, array $data): bool
    {
        $sql = 'UPDATE usuarios SET nome_completo = :nome_completo, email = :email, endereco = :endereco,
                whatsapp = :whatsapp, numero_decreto = :numero_decreto, data_nascimento = :data_nascimento
                WHERE id = :id AND perfil = "funcionario"';
        $stmt = Database::connection()->prepare($sql);
        return $stmt->execute(['id' => $id] + $data);
    }

    public function toggleEmployeeStatus(int $id, bool $active): bool
    {
        $stmt = Database::connection()->prepare('UPDATE usuarios SET ativo = :ativo WHERE id = :id AND perfil = "funcionario"');
        return $stmt->execute(['id' => $id, 'ativo' => $active ? 1 : 0]);
    }

    public function decreeExists(string $decree, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM usuarios WHERE numero_decreto = :decreto' . ($ignoreId ? ' AND id != :id' : '') . ' LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $params = ['decreto' => $decree];
        if ($ignoreId) {
            $params['id'] = $ignoreId;
        }
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM usuarios WHERE email = :email' . ($ignoreId ? ' AND id != :id' : '') . ' LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $params = ['email' => $email];
        if ($ignoreId) {
            $params['id'] = $ignoreId;
        }
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function existsEmployeeById(int $id): bool
    {
        $stmt = Database::connection()->prepare('SELECT id FROM usuarios WHERE id = :id AND perfil = "funcionario" LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool)$stmt->fetch();
    }

    public function allActiveEmployees(): array
    {
        $sql = "SELECT id, nome_completo FROM usuarios WHERE perfil = 'funcionario' AND ativo = 1 ORDER BY nome_completo";
        return Database::connection()->query($sql)->fetchAll();
    }

    public function findActiveMasterId(): ?int
    {
        $stmt = Database::connection()->query("SELECT id FROM usuarios WHERE perfil = 'master' AND ativo = 1 ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch();
        return isset($row['id']) ? (int)$row['id'] : null;
    }
}
