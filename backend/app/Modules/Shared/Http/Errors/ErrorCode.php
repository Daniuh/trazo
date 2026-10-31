<?php

declare(strict_types=1);

// Códigos de error generales de la API. Los de negocio los define cada módulo en su dominio.
// Documentado en docs/api/errores.md

namespace App\Modules\Shared\Http\Errors;

enum ErrorCode: string
{
    case MalformedRequest = 'MALFORMED_REQUEST';
    case Unauthenticated = 'UNAUTHENTICATED';
    case ForbiddenAction = 'FORBIDDEN_ACTION';
    case ResourceNotFound = 'RESOURCE_NOT_FOUND';
    case MethodNotAllowed = 'METHOD_NOT_ALLOWED';
    case ValidationFailed = 'VALIDATION_FAILED';
    case TooManyRequests = 'TOO_MANY_REQUESTS';
    case ServiceUnavailable = 'SERVICE_UNAVAILABLE';
    case InternalError = 'INTERNAL_ERROR';

    public function status(): int
    {
        return match ($this) {
            self::MalformedRequest => 400,
            self::Unauthenticated => 401,
            self::ForbiddenAction => 403,
            self::ResourceNotFound => 404,
            self::MethodNotAllowed => 405,
            self::ValidationFailed => 422,
            self::TooManyRequests => 429,
            self::ServiceUnavailable => 503,
            self::InternalError => 500,
        };
    }

    // El código general que corresponde a un estado HTTP sin mapeo propio
    public static function fromStatus(int $status): self
    {
        foreach (self::cases() as $case) {
            if ($case->status() === $status) {
                return $case;
            }
        }

        return $status >= 500 ? self::InternalError : self::MalformedRequest;
    }
}
