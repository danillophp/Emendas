<?php

namespace App\Models;

use App\Core\Database;

class Demand
{
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
                ORDER BY d.prazo_entrega ASC
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

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO demandas (emenda, nome_politico, data_emenda, tipo_emenda, observacao, prazo_entrega, funcionario_id, status, criado_por)
                VALUES (:emenda, :nome_politico, :data_emenda, :tipo_emenda, :observacao, :prazo_entrega, :funcionario_id, :status, :criado_por)';
        return Database::connection()->prepare($sql)->execute($data);
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE demandas SET emenda = :emenda, nome_politico = :nome_politico, data_emenda = :data_emenda, tipo_emenda = :tipo_emenda,
                observacao = :observacao, prazo_entrega = :prazo_entrega, funcionario_id = :funcionario_id, status = :status
                WHERE id = :id';
        return Database::connection()->prepare($sql)->execute(['id' => $id] + $data);
    }

    public function delete(int $id): bool
    {
        return Database::connection()->prepare('DELETE FROM demandas WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Retorna apenas demandas do funcionário logado.
     */
    public function byEmployee(int $employeeId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM demandas WHERE funcionario_id = :id ORDER BY prazo_entrega ASC');
        $stmt->execute(['id' => $employeeId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca uma demanda específica pertencente ao funcionário.
     * Usado para impedir acesso indevido por manipulação de ID.
     */
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

    /**
     * Funcionário só pode concluir suas próprias demandas.
     */
    public function markCompleted(int $id, int $employeeId, string $completionNote): bool
    {
        $sql = 'UPDATE demandas
                SET status = "concluida", data_conclusao = NOW(), observacao_conclusao = :obs
                WHERE id = :id AND funcionario_id = :funcionario_id AND status != "concluida"';

        return Database::connection()->prepare($sql)->execute([
            'id' => $id,
            'funcionario_id' => $employeeId,
            'obs' => $completionNote,
        ]);
    }

    /**
     * Regra automática de vencimento:
     * toda demanda não concluída cujo prazo já expirou vira "atrasada".
     */
    public function refreshOverdueStatuses(): int
    {
        $sql = 'UPDATE demandas
                SET status = "atrasada"
                WHERE status != "concluida" AND prazo_entrega < NOW()';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function dueIn24Hours(): array
    {
        $sql = 'SELECT d.*, u.nome_completo AS funcionario_nome
                FROM demandas d
                JOIN usuarios u ON u.id = d.funcionario_id
                WHERE d.status != "concluida" AND d.prazo_entrega BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
                ORDER BY d.prazo_entrega ASC';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function upcomingDeadlines(int $limit = 8): array
    {
        $sql = 'SELECT d.id, d.emenda, d.prazo_entrega, d.status, u.nome_completo AS funcionario_nome
                FROM demandas d
                JOIN usuarios u ON u.id = d.funcionario_id
                WHERE d.status IN ("pendente", "em_andamento")
                ORDER BY d.prazo_entrega ASC
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

        $stats = ['total' => 0, 'pendente' => 0, 'em_andamento' => 0, 'concluida' => 0, 'atrasada' => 0];
        foreach ($rows as $row) {
            $stats['total'] += (int)$row['total'];
            $stats[$row['status']] = (int)$row['total'];
        }
        return $stats;
    }

    public function employeeSummary(int $employeeId): array
    {
        $stats = $this->stats($employeeId);

        $sql = 'SELECT COUNT(*) AS proximas
                FROM demandas
                WHERE funcionario_id = :funcionario_id
                AND status != "concluida"
                AND prazo_entrega BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['funcionario_id' => $employeeId]);
        $row = $stmt->fetch();
        $stats['proximas_prazo'] = (int)($row['proximas'] ?? 0);

        return $stats;
    }

    private function buildFilters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'd.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['funcionario_id'])) {
            $where[] = 'd.funcionario_id = :funcionario_id';
            $params['funcionario_id'] = (int)$filters['funcionario_id'];
        }

        if (!empty($filters['prazo_de'])) {
            $where[] = 'd.prazo_entrega >= :prazo_de';
            $params['prazo_de'] = $filters['prazo_de'] . ' 00:00:00';
        }

        if (!empty($filters['prazo_ate'])) {
            $where[] = 'd.prazo_entrega <= :prazo_ate';
            $params['prazo_ate'] = $filters['prazo_ate'] . ' 23:59:59';
        }

        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$sqlWhere, $params];
    }
}
