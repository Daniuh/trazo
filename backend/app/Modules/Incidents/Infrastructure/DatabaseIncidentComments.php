<?php

declare(strict_types=1);

// Mensajes de la conversación con el constructor de consultas (sin Eloquent): una sola consulta con la persona que escribió cada uno.
// Escribir y borrar emiten IncidentCommented, que sale cuando la transacción se confirma.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\CommentRecord;
use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Data\Person;
use App\Modules\Incidents\Application\Events\IncidentCommented;
use App\Modules\Incidents\Application\Ports\IncidentComments;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use stdClass;

final readonly class DatabaseIncidentComments implements IncidentComments
{
    public function add(IncidentRecord $incident, int $authorId, string $body): CommentRecord
    {
        $ulid = Str::lower((string) Str::ulid());
        DB::table('incident_comments')->insert([
            'ulid' => $ulid,
            'incident_id' => $incident->id,
            'user_id' => $authorId,
            'body' => $body,
            'created_at' => now(),
        ]);
        $comment = $this->find($incident->id, $ulid) ?? throw new LogicException('El mensaje recién escrito no se encontró.');
        $this->announce($incident, $comment, removed: false);

        return $comment;
    }

    public function find(int $incidentId, string $ulid): ?CommentRecord
    {
        $row = $this->query()->where('c.incident_id', $incidentId)->where('c.ulid', $ulid)->first();

        return $row === null ? null : $this->record($row);
    }

    public function markDeleted(IncidentRecord $incident, CommentRecord $comment): CommentRecord
    {
        DB::table('incident_comments')->where('id', $comment->id)->update(['body' => null, 'deleted_at' => now()]);
        $this->announce($incident, $comment, removed: true);

        return $this->find($incident->id, $comment->ulid) ?? $comment;
    }

    public function page(int $incidentId, ?string $before, int $limit): array
    {
        $query = $this->query()->where('c.incident_id', $incidentId)->orderByDesc('c.id')->limit($limit);
        if ($before !== null) {
            // Por id y no por fecha: dos mensajes en el mismo segundo no se saltan ni se repiten
            $query->where('c.id', '<', fn (Builder $q) => $q->select('id')->from('incident_comments')->where('ulid', $before));
        }

        return array_values($query->get()->map(fn (stdClass $row): CommentRecord => $this->record($row))->all());
    }

    public function count(int $incidentId): int
    {
        return DB::table('incident_comments')->where('incident_id', $incidentId)->whereNull('deleted_at')->count();
    }

    private function query(): Builder
    {
        return DB::table('incident_comments as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->select(['c.id', 'c.ulid', 'c.incident_id', 'c.body', 'c.created_at', 'c.deleted_at', 'u.id as author_id', 'u.ulid as author_ulid', 'u.name as author_name']);
    }

    private function record(stdClass $row): CommentRecord
    {
        return new CommentRecord(
            (int) $row->id,
            (string) $row->ulid,
            (int) $row->incident_id,
            new Person((int) $row->author_id, (string) $row->author_ulid, (string) $row->author_name),
            $row->body === null ? null : (string) $row->body,
            new DateTimeImmutable((string) $row->created_at.' UTC'),
            $row->deleted_at === null ? null : new DateTimeImmutable((string) $row->deleted_at.' UTC'),
        );
    }

    private function announce(IncidentRecord $incident, CommentRecord $comment, bool $removed): void
    {
        event(new IncidentCommented(
            $incident->id,
            $incident->ulid,
            $comment->id,
            $incident->creator->id,
            $incident->assignee?->id,
            $comment->author->id,
            $removed,
        ));
    }
}
