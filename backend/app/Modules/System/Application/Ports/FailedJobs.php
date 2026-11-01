<?php

declare(strict_types=1);

// Jobs que agotaron sus intentos: listarlos sin su contenido, reintentarlos y descartarlos.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Ports;

use App\Modules\System\Application\Data\FailedJob;
use App\Modules\System\Application\Data\FailedJobList;

interface FailedJobs
{
    public function recent(int $limit): FailedJobList;

    public function count(): int;

    public function find(string $id): ?FailedJob;

    /**
     * @param  list<string>|null  $ids  null: todos
     * @return int Cuántos volvieron a la cola
     */
    public function retry(?array $ids): int;

    public function discard(string $id): void;
}
