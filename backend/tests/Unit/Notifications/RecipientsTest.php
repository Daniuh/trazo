<?php

declare(strict_types=1);

// A quién avisa cada cosa (Recipients). Documentado en docs/backend/notifications.md

use App\Modules\Notifications\Domain\Coordinators;
use App\Modules\Notifications\Domain\NotificationCategory;
use App\Modules\Notifications\Domain\NotificationType;
use App\Modules\Notifications\Domain\Participants;
use App\Modules\Notifications\Domain\Recipients;

it('un mensaje llega a creador, asignado y a quienes escribieron, sin repetir ni incluir al autor', function (): void {
    $people = new Participants(creator: 1, assignee: 2, commenters: [3, 2, 4]);

    expect(Recipients::forComment(4, $people))->toBe([1, 2, 3])
        ->and(Recipients::forComment(1, new Participants(1, null, [1])))->toBe([]);
});

it('cada registro del historial avisa a quien corresponde', function (string $activity, array $expected): void {
    $people = new Participants(creator: 1, assignee: 2, reopenRequester: 3);

    expect(Recipients::forActivity($activity, 9, $people))->toBe($expected);
})->with([
    'asignado' => ['assigned', [2 => NotificationType::IncidentAssigned]],
    'creado con asignado' => ['created', [2 => NotificationType::IncidentAssigned]],
    'cambio de estado' => ['status_changed', [1 => NotificationType::StatusChanged, 2 => NotificationType::StatusChanged]],
    'reabierto' => ['reopened', [1 => NotificationType::IncidentReopened, 2 => NotificationType::IncidentReopened, 3 => NotificationType::IncidentReopened]],
    'reapertura rechazada' => ['reopen_rejected', [3 => NotificationType::ReopenRejected]],
    'editado' => ['updated', []],
]);

it('al crear avisa a quienes coordinan, con un solo aviso por persona y el más importante', function (
    Participants $people,
    array $expected,
): void {
    $coordinators = new Coordinators(assigners: [5, 6], reopenApprovers: [5]);

    // Quien crea el incidente es quien hizo la acción
    expect(Recipients::forActivity('created', $people->creator, $people, $coordinators))->toBe($expected);
})->with([
    'sin asignar' => [new Participants(1, null), [5 => NotificationType::UnassignedCreated, 6 => NotificationType::UnassignedCreated]],
    'asignado' => [new Participants(1, 2), [2 => NotificationType::IncidentAssigned]],
    'crítico sin asignar' => [new Participants(1, null, critical: true), [5 => NotificationType::CriticalCreated, 6 => NotificationType::CriticalCreated]],
    'crítico asignado' => [new Participants(1, 5, critical: true), [5 => NotificationType::CriticalCreated, 6 => NotificationType::CriticalCreated]],
    'lo crea quien coordina' => [new Participants(5, null), [6 => NotificationType::UnassignedCreated]],
]);

it('una reapertura pedida va a quienes pueden aprobarla, menos a quien la pidió', function (): void {
    $coordinators = new Coordinators(assigners: [5], reopenApprovers: [5, 7]);

    expect(Recipients::forActivity('reopen_requested', 7, new Participants(1, 2), $coordinators))
        ->toBe([5 => NotificationType::ReopenRequested]);
});

it('un vencimiento va a quien lo atiende y a quienes pueden asignar, sin repetir', function (): void {
    expect(Recipients::forOverdue(2, new Coordinators(assigners: [5, 2])))->toBe([2, 5])
        ->and(Recipients::forOverdue(null, new Coordinators))->toBe([]);
});

it('nadie recibe aviso de lo que hizo él mismo', function (): void {
    $people = new Participants(creator: 1, assignee: 2);

    expect(Recipients::forActivity('assigned', 2, $people))->toBe([])
        ->and(Recipients::forActivity('status_changed', 1, $people))->toBe([2 => NotificationType::StatusChanged])
        ->and(Recipients::forActivity('assigned', 1, new Participants(1, null)))->toBe([]);
});

it('los mensajes van a Conversaciones y lo demás a Actividad', function (): void {
    expect(NotificationType::CommentReceived->category())->toBe(NotificationCategory::Conversations)
        ->and(NotificationType::StatusChanged->category())->toBe(NotificationCategory::Activity);
});
