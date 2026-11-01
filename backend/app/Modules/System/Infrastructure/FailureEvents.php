<?php

declare(strict_types=1);

// Convierte en eventos del sistema las fallas que nadie ve: errores no controlados de la API (500) y jobs que
// agotaron sus intentos. Los mensajes van sin datos sensibles: de un error de base de datos solo queda su código
// SQLSTATE, nunca la consulta. Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\Shared\Application\Ports\SystemEvents;
use App\Modules\Shared\Application\SystemEventType;
use Illuminate\Database\QueryException;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Str;
use Throwable;

final readonly class FailureEvents
{
    public function __construct(private SystemEvents $events) {}

    public function unhandled(Throwable $error): void
    {
        // En la cola cada intento fallido pasaría por aquí: la falla se registra una vez, como job fallido
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $file = Str::after($error->getFile(), base_path().DIRECTORY_SEPARATOR);
        $request = request();
        $this->events->record(SystemEventType::UnhandledError, $error::class."@{$file}:{$error->getLine()}", $this->describe($error), [
            'exception' => $error::class,
            'file' => $file,
            'line' => $error->getLine(),
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
        ]);
    }

    public function jobFailed(JobFailed $event): void
    {
        $job = class_basename($event->job->resolveName());
        $this->events->record(SystemEventType::JobFailed, $job.'|'.$event->exception::class, "Falló el job {$job}: ".class_basename($event->exception), [
            'job' => $job,
            'queue' => $event->job->getQueue(),
            'exception' => $event->exception::class,
        ]);
    }

    private function describe(Throwable $error): string
    {
        if ($error instanceof QueryException) {
            return 'Error de base de datos (SQLSTATE '.($error->errorInfo[0] ?? 'desconocido').')';
        }

        return class_basename($error).': '.Str::limit($error->getMessage(), 200);
    }
}
