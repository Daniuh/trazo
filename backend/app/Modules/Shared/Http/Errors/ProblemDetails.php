<?php

declare(strict_types=1);

// Error de la API en formato RFC 9457 (application/problem+json).
// Documentado en docs/api/errores.md

namespace App\Modules\Shared\Http\Errors;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final readonly class ProblemDetails
{
    /**
     * @param  array<string, scalar>  $params
     * @param  array<string, list<array{code: string, params?: array<string, scalar>}>>  $errors
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public int $status,
        public string $code,
        public array $params = [],
        public array $errors = [],
        public array $headers = [],
    ) {}

    public static function fromCode(ErrorCode $code): self
    {
        return new self($code->status(), $code->value);
    }

    public function toResponse(string $traceId): JsonResponse
    {
        $body = array_filter([
            'type' => 'urn:trazo:error:'.Str::of($this->code)->lower()->replace('_', '-'),
            'title' => Str::of($this->code)->lower()->replace('_', ' ')->ucfirst()->toString(),
            'status' => $this->status,
            'code' => $this->code,
            'params' => $this->params,
            'errors' => $this->errors,
            'trace_id' => $traceId,
        ], fn (mixed $value): bool => $value !== []);

        return new JsonResponse($body, $this->status, [
            ...$this->headers,
            'Content-Type' => 'application/problem+json',
        ]);
    }
}
