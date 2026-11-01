<?php

declare(strict_types=1);

// Forma pública de la auditoría. Cada registro trae "links": qué puede abrir quien mira (el incidente, la ficha de la
// persona o la del rol), calculado con las mismas reglas de cada módulo. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Http;

use App\Modules\Audit\Application\Data\AdminAuditEntry;
use App\Modules\Audit\Application\Data\AuditCatalog;
use App\Modules\Audit\Application\Data\IncidentAuditEntry;
use App\Modules\Audit\Application\Data\PersonRef;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\IncidentAccess;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Users\Domain\Administrator;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final readonly class AuditResource
{
    public function __construct(private IncidentAccess $incidents) {}

    /**
     * @return array<string, mixed>
     */
    public function incident(IncidentAuditEntry $entry, Administrator $viewer): array
    {
        $actor = new Actor($viewer->id, $viewer->permissions());

        return [
            ...$this->common($entry->id, $entry->createdAt, $entry->typeCode, $entry->typeName, $entry->actor),
            'incident' => [
                'id' => $entry->incidentId,
                'code' => $entry->incidentCode,
                'title' => $entry->incidentTitle,
                'deleted' => $entry->incident->deleted,
            ],
            'changes' => $entry->changes,
            'note' => $entry->note,
            'ip_address' => $entry->ipAddress,
            'user_agent' => $entry->userAgent,
            'links' => ['incident' => $this->incidents->denial(IncidentAction::View, $actor, $entry->incident) === null],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function admin(AdminAuditEntry $entry, Administrator $viewer): array
    {
        $role = $entry->targetRole;

        return [
            ...$this->common($entry->id, $entry->createdAt, $entry->typeCode, $entry->typeName, $entry->actor),
            'target_user' => $entry->targetUser === null ? null : $this->person($entry->targetUser),
            'target_role' => $role === null ? null : ['code' => $role->code, 'name' => $role->name, 'deleted' => $role->deleted],
            'details' => $entry->details,
            'ip_address' => $entry->ipAddress,
            'user_agent' => $entry->userAgent,
            'links' => [
                'user' => $entry->targetUser !== null && $viewer->can('users.view'),
                'role' => $role !== null && ! $role->deleted && $viewer->can('roles.manage'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function catalog(AuditCatalog $catalog): array
    {
        return [
            'incident_types' => $catalog->incidentTypes,
            'admin_types' => $catalog->adminTypes,
            'roles' => $catalog->roles,
            'permissions' => $catalog->permissions,
            'people' => array_map($this->person(...), $catalog->people),
        ];
    }

    public static function date(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM);
    }

    /**
     * @return array<string, mixed>
     */
    private function common(int $id, DateTimeImmutable $createdAt, string $typeCode, string $typeName, PersonRef $actor): array
    {
        return [
            'id' => $id,
            'created_at' => self::date($createdAt),
            'type' => ['code' => $typeCode, 'name' => $typeName],
            'user' => $this->person($actor),
        ];
    }

    /**
     * @return array{id: string, name: string}
     */
    private function person(PersonRef $person): array
    {
        return ['id' => $person->id, 'name' => $person->name];
    }
}
