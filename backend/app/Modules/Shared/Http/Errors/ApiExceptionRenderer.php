<?php

declare(strict_types=1);

// Punto único donde cualquier excepción de la API se convierte en un error RFC 9457 sin detalles técnicos.
// Documentado en docs/api/errores.md

namespace App\Modules\Shared\Http\Errors;

use App\Modules\Shared\Domain\Exceptions\AuthenticationFailedException;
use App\Modules\Shared\Domain\Exceptions\ConflictException;
use App\Modules\Shared\Domain\Exceptions\DomainException;
use App\Modules\Shared\Domain\Exceptions\ResourceNotFoundException;
use App\Modules\Shared\Domain\Exceptions\TemporarilyLockedException;
use App\Modules\Shared\Http\Middleware\AssignRequestId;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ApiExceptionRenderer
{
    public function __construct(private readonly ValidationErrorFormatter $validation) {}

    public function render(Throwable $exception, Request $request): JsonResponse
    {
        return $this->toProblem($exception)->toResponse(AssignRequestId::current($request));
    }

    private function toProblem(Throwable $e): ProblemDetails
    {
        return match (true) {
            $e instanceof ValidationException => new ProblemDetails(
                422,
                ErrorCode::ValidationFailed->value,
                errors: $this->validation->format($e->validator),
            ),
            $e instanceof DomainException => new ProblemDetails(
                $this->statusFor($e),
                $e->errorCode(),
                $e->params(),
                headers: $e instanceof TemporarilyLockedException ? ['Retry-After' => (string) $e->retryAfterSeconds()] : [],
            ),
            $e instanceof AuthenticationException => ProblemDetails::fromCode(ErrorCode::Unauthenticated),
            $e instanceof AuthorizationException => ProblemDetails::fromCode(ErrorCode::ForbiddenAction),
            $e instanceof ModelNotFoundException => ProblemDetails::fromCode(ErrorCode::ResourceNotFound),
            $e instanceof HttpExceptionInterface => $this->fromHttp($e),
            default => ProblemDetails::fromCode(ErrorCode::InternalError),
        };
    }

    // Las reglas de negocio violadas (RuleViolationException) son 422
    private function statusFor(DomainException $e): int
    {
        return match (true) {
            $e instanceof AuthenticationFailedException => 401,
            $e instanceof ResourceNotFoundException => 404,
            $e instanceof ConflictException => 409,
            $e instanceof TemporarilyLockedException => 423,
            default => 422,
        };
    }

    // Errores HTTP del framework (404 de ruta, 405, 429, 503…), conservando cabeceras útiles
    private function fromHttp(HttpExceptionInterface $e): ProblemDetails
    {
        $code = ErrorCode::fromStatus($e->getStatusCode());
        $headers = array_intersect_key($e->getHeaders(), array_flip(['Retry-After', 'Allow']));

        return new ProblemDetails($code->status(), $code->value, headers: array_map('strval', $headers));
    }
}
