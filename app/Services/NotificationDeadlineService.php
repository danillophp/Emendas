<?php

namespace App\Services;

use App\Models\Demand;
use App\Models\Notification;

class NotificationDeadlineService
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification()
    ) {
    }

    public function runAutomationForMaster(int $masterId): int
    {
        $alertsCreated = 0;

        foreach ($this->demands->dueIn24Hours() as $item) {
            $demandId = (int)$item['id'];
            if ($this->notifications->existsRecent($masterId, 'prazo_24h', $demandId, 24)) {
                continue;
            }

            $this->notifications->notify(
                $masterId,
                'Prazo próximo (24h)',
                sprintf('A demanda "%s" atribuída para %s vence em até 24 horas.', $item['emenda'], $item['funcionario_nome']),
                'prazo_24h',
                $demandId,
                'demandas'
            );
            $alertsCreated++;
        }

        return $alertsCreated;
    }

    public function notifyDemandAssignedToEmployee(int $employeeId, array $demand): bool
    {
        return $this->notifications->notify(
            $employeeId,
            'Nova demanda atribuída',
            sprintf('A demanda "%s" foi cadastrada e atribuída a você.', $demand['emenda'] ?? 'Sem título'),
            'demanda_nova',
            (int)($demand['id'] ?? 0),
            'demandas'
        );
    }

    public function notifyDemandUpdatedToEmployee(int $employeeId, array $demand): bool
    {
        return $this->notifications->notify(
            $employeeId,
            'Demanda atualizada',
            sprintf('A demanda "%s" recebeu atualização do Master.', $demand['emenda'] ?? 'Sem título'),
            'demanda_atualizada',
            (int)($demand['id'] ?? 0),
            'demandas'
        );
    }

    public function notifyDemandEventToMaster(int $masterId, array $demand, string $title, string $message, string $type): bool
    {
        return $this->notifications->notify(
            $masterId,
            $title,
            $message,
            $type,
            (int)($demand['id'] ?? 0),
            'demandas'
        );
    }
}
