<?php

declare(strict_types=1);

// Jobs fallidos desde la tabla failed_jobs de Laravel. El contenido (payload) nunca sale de aquí: solo se lee el
// nombre del job. El error se recorta a su primera línea. Reintentar y descartar usan los comandos de Laravel.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\System\Application\Data\FailedJob;
use App\Modules\System\Application\Data\FailedJobList;
use App\Modules\System\Application\Ports\FailedJobs;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

final class DatabaseFailedJobs implements FailedJobs
{
    private const ERROR_LENGTH = 300;

    public function recent(int $limit): FailedJobList
    {
        $rows = DB::table('failed_jobs')->orderByDesc('failed_at')->orderByDesc('id')->limit($limit)->get();

        return new FailedJobList(array_values(array_map($this->toJob(...), $rows->all())), $this->count());
    }

    public function count(): int
    {
        return DB::table('failed_jobs')->count();
    }

    public function find(string $id): ?FailedJob
    {
        $row = DB::table('failed_jobs')->where('uuid', $id)->first();

        return $row instanceof stdClass ? $this->toJob($row) : null;
    }

    public function retry(?array $ids): int
    {
        $count = $ids === null ? $this->count() : count($ids);
        Artisan::call('queue:retry', ['id' => $ids ?? ['all']]);

        return $count;
    }

    public function discard(string $id): void
    {
        Artisan::call('queue:forget', ['id' => $id]);
    }

    private function toJob(stdClass $row): FailedJob
    {
        $payload = json_decode((string) $row->payload, true);
        $name = is_array($payload) && is_string($payload['displayName'] ?? null) ? $payload['displayName'] : 'Job desconocido';
        $firstLine = strtok((string) $row->exception, "\n");

        return new FailedJob(
            (string) $row->uuid,
            class_basename($name),
            (string) $row->queue,
            new DateTimeImmutable((string) $row->failed_at, new DateTimeZone('UTC')),
            Str::limit($firstLine === false ? '' : $firstLine, self::ERROR_LENGTH),
        );
    }
}
