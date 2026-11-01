<?php

declare(strict_types=1);

// Catálogo de tipos de notificación, con la pestaña en la que aparece cada uno. Idempotente: agrega los que falten.
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use App\Modules\Notifications\Domain\NotificationType;
use Database\Seeders\Concerns\InsertsMissingRows;
use Illuminate\Database\Seeder;

class NotificationTypeSeeder extends Seeder
{
    use InsertsMissingRows;

    // código => nombre (la pestaña sale del tipo)
    private const NAMES = [
        'comment_received' => 'Mensaje en una conversación',
        'incident_assigned' => 'Incidente asignado',
        'status_changed' => 'Cambio de estado',
        'incident_reopened' => 'Incidente reabierto',
        'reopen_rejected' => 'Reapertura rechazada',
        'unassigned_created' => 'Incidente nuevo sin asignar',
        'critical_created' => 'Incidente crítico reportado',
        'reopen_requested' => 'Reapertura pendiente de aprobar',
        'incident_overdue' => 'Incidente vencido',
    ];

    public function run(): void
    {
        $this->insertMissing('notification_types', 'code', array_map(
            fn (NotificationType $type): array => [
                'code' => $type->value,
                'name' => self::NAMES[$type->value],
                'category' => $type->category()->value,
            ],
            NotificationType::cases(),
        ));
    }
}
