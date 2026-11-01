<?php

declare(strict_types=1);

// Casos de uso de los jobs fallidos: verlos (sin su contenido), reintentarlos (uno, varios o todos) y descartar uno.
// Reintentar y descartar son acciones de sistema: permiso, reautenticación y registro. Documentado en docs/backend/system.md

namespace App\Modules\System\Application;

use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\System\Application\Data\FailedJobList;
use App\Modules\System\Application\Ports\FailedJobs;
use App\Modules\System\Domain\Exceptions\SystemExceptions;
use App\Modules\System\Domain\Operator;

final readonly class ManageFailedJobs
{
    private const LIST_LIMIT = 50;

    public function __construct(
        private SystemGuard $guard,
        private FailedJobs $jobs,
        private AdminLog $log,
    ) {}

    public function list(Operator $operator): FailedJobList
    {
        $this->guard->require($operator, Operator::HEALTH);

        return $this->jobs->recent(self::LIST_LIMIT);
    }

    /**
     * @param  list<string>|null  $ids  null: todos
     */
    public function retry(Operator $operator, ?array $ids, ?string $code, RequestContext $context): int
    {
        $this->guard->authorizeAction($operator, $code);

        if ($ids !== null) {
            foreach ($ids as $id) {
                $this->jobs->find($id) ?? throw SystemExceptions::failedJobNotFound();
            }
        } elseif ($this->jobs->count() === 0) {
            throw SystemExceptions::noFailedJobs();
        }

        $retried = $this->jobs->retry($ids);
        $this->log->record(AdminActionType::SystemJobsRetried, $operator->id, null, ['count' => $retried], $context);

        return $retried;
    }

    public function discard(Operator $operator, string $id, ?string $code, RequestContext $context): void
    {
        $this->guard->authorizeAction($operator, $code);
        $job = $this->jobs->find($id) ?? throw SystemExceptions::failedJobNotFound();

        $this->jobs->discard($id);
        $this->log->record(AdminActionType::SystemJobDiscarded, $operator->id, null, ['job' => $job->job, 'queue' => $job->queue], $context);
    }
}
