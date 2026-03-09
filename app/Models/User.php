<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de usuários para autenticação e gestão administrativa.
 */
class User
{
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
