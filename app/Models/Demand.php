<?php

namespace App\Models;

use App\Core\Database;

class Demand
{
    public const STATUS_ALLOWED = ['pendente', 'cadastrada', 'concluído'];
    public const PROCESS_TYPES = ['prestacao_de_conta', 'cadastro_de_emenda', 'outros'];
    public const AMENDMENT_TYPES = ['federal', 'estadual', 'municipal'];

    public const HISTORY_ACTION_CREATE = 'create';
    public const HISTORY_ACTION_EDIT = 'edit';
    public const HISTORY_ACTION_STATUS_CHANGE = 'status_change';
    public const HISTORY_ACTION_EMPLOYEE_NOTE = 'employee_note';

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
        $sql = 'INSERT INTO demandas (emenda, nome_politico, numero_processo_sei, tipo_processo, tipo_processo_outros, tipo_emenda, data_prazo_resposta, data_cadastro_emenda,
                    observacao, anexo_emenda, anexo_nome_original, funcionario_id, status, criado_por, data_ultima_atualizacao)
                VALUES (:emenda, :nome_politico, :numero_processo_sei, :tipo_processo, :tipo_processo_outros, :tipo_emenda, :data_prazo_resposta, :data_cadastro_emenda,
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
                    numero_processo_sei = :numero_processo_sei,
                    tipo_processo = :tipo_processo,
                    tipo_processo_outros = :tipo_processo_outros,
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

    public function updateProgressByEmployee(int $id, int $employeeId, string $status, string $employeeNote): bool
    {
        $sql = 'UPDATE demandas
                SET status = :status,
                    observacao_funcionario = :observacao_funcionario,
                    data_ultima_atualizacao = NOW(),
                    updated_at = NOW()
                WHERE id = :id AND funcionario_id = :funcionario_id';

        return Database::connection()->prepare($sql)->execute([
            'id' => $id,
            'funcionario_id' => $employeeId,
            'status' => $status,
            'observacao_funcionario' => $employeeNote,
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

        $stats = ['total' => 0, 'pendente' => 0, 'cadastrada' => 0, 'concluído' => 0];
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



    public function registerHistory(
        int $demandId,
        ?int $userId,
        string $userName,
        string $action,
        ?string $previousStatus,
        ?string $newStatus,
        ?string $note = null
    ): bool {
        $sql = 'INSERT INTO demandas_historico (demanda_id, usuario_id, usuario_nome, acao, status_anterior, status_novo, observacao)
                VALUES (:demanda_id, :usuario_id, :usuario_nome, :acao, :status_anterior, :status_novo, :observacao)';

        return Database::connection()->prepare($sql)->execute([
            'demanda_id' => $demandId,
            'usuario_id' => $userId,
            'usuario_nome' => trim($userName) !== '' ? trim($userName) : 'Sistema',
            'acao' => $action,
            'status_anterior' => $previousStatus,
            'status_novo' => $newStatus,
            'observacao' => $note !== null && trim($note) !== '' ? trim($note) : null,
        ]);
    }

    public function registerEmployeeHistory(
        int $demandId,
        int $employeeId,
        string $employeeName,
        string $previousStatus,
        string $newStatus,
        ?string $note = null
    ): bool {
        $action = $previousStatus !== $newStatus ? self::HISTORY_ACTION_STATUS_CHANGE : self::HISTORY_ACTION_EMPLOYEE_NOTE;

        return $this->registerHistory(
            $demandId,
            $employeeId,
            $employeeName,
            $action,
            $previousStatus,
            $newStatus,
            $note
        );
    }

    public function historiesByDemandIds(array $demandIds, int $limitPerDemand = 50): array
    {
        $demandIds = array_values(array_unique(array_map('intval', $demandIds)));
        $demandIds = array_filter($demandIds, static fn (int $id): bool => $id > 0);

        if ($demandIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($demandIds as $i => $id) {
            $key = ':d' . $i;
            $placeholders[] = $key;
            $params[$key] = $id;
        }

        $sql = 'SELECT h.*, u.nome_completo AS usuario_nome_atual
                FROM demandas_historico h
                LEFT JOIN usuarios u ON u.id = h.usuario_id
                WHERE h.demanda_id IN (' . implode(',', $placeholders) . ')
                ORDER BY h.created_at DESC, h.id DESC';

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $demandId = (int)$row['demanda_id'];
            if (!isset($grouped[$demandId])) {
                $grouped[$demandId] = [];
            }
            if (count($grouped[$demandId]) >= $limitPerDemand) {
                continue;
            }
            $grouped[$demandId][] = $row;
        }

        return $grouped;
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
