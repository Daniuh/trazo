<?php

declare(strict_types=1);

// Eventos del sistema que se registran. El valor es el código del catálogo system_event_types, donde viven su
// nombre, categoría y severidad. Documentado en docs/backend/system.md

namespace App\Modules\Shared\Application;

enum SystemEventType: string
{
    case UnhandledError = 'unhandled_error';
    case JobFailed = 'job_failed';
    case QueueBacklog = 'queue_backlog';
    case ServiceDown = 'service_down';
    case ExternalServiceUnavailable = 'external_service_unavailable';
    case LoginLocked = 'login_locked';
    case MfaAttemptsExceeded = 'mfa_attempts_exceeded';
    case ResetCodeVoided = 'reset_code_voided';
    case ReauthenticationLocked = 'reauthentication_locked';
    case AccessDeniedRepeated = 'access_denied_repeated';
}
