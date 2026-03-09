<?php

namespace App\Services;

use App\Models\Demand;
use App\Models\Notification;

/**
 * Serviço responsável pelo módulo de notificações e controle de prazo.
 *
 * Centraliza regras reutilizáveis para:
 * - alerta de demandas com 24h ou menos;
 * - prevenção de notificações duplicadas excessivas;
 * - notificação de conclusão de demanda ao Master.
 */
class NotificationDeadlineService
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification()
    ) {
    }

    /**
     * Atualiza status atrasado e dispara alertas de 24h para o Master.
     *
     * @return array{overdue_updated:int, alerts_created:int}
     */
    public function runAutomationForMaster(int $masterId): array
    {
        $overdueUpdated = $this->demands->refreshOverdueStatuses();
        $alertsCreated = 0;

        foreach ($this->demands->dueIn24Hours() as $item) {
            $demandId = (int)$item['id'];

            // Evita repetição excessiva da mesma notificação em janelas curtas.
            if ($this->notifications->existsRecent($masterId, 'prazo_24h', $demandId, 24)) {
                continue;
            }

            $this->notifications->notify(
                $masterId,
                'Prazo próximo (<= 24h)',
                sprintf(
                    'Demanda #%d (%s) vence em até 24h. Responsável: %s.',
                    $demandId,
                    $item['emenda'],
                    $item['funcionario_nome']
                ),
                'prazo_24h',
                $demandId,
                'demandas'
            );
            $alertsCreated++;
        }

        return [
            'overdue_updated' => $overdueUpdated,
            'alerts_created' => $alertsCreated,
        ];
    }

    /**
     * Gera notificação de conclusão para o Master com referência da demanda.
     */
    public function notifyDemandCompletedForMaster(int $masterId, array $demand, string $employeeName): bool
    {
        $demandId = (int)($demand['id'] ?? 0);

        return $this->notifications->notify(
            $masterId,
            'Demanda concluída',
            sprintf('%s concluiu a demanda #%d (%s).', $employeeName, $demandId, $demand['emenda'] ?? 'sem título'),
            'conclusao_demanda',
            $demandId,
            'demandas'
        );
    }
}
