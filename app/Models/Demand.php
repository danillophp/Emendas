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
