<?php

declare(strict_types=1);

// Contrato OpenAPI de la API, generado desde el código con Scramble. Solo el JSON y solo para quien tiene el permiso de
// salud del sistema: la interfaz visual de Scramble carga scripts de una CDN y en línea, y la CSP no lo permite.
// Documentado en docs/api/index.md

namespace App\Modules\System\Http;

use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\System\Domain\Operator;
use Dedoc\Scramble\CacheableGenerator;
use Dedoc\Scramble\Scramble;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ApiDocsController
{
    public function __construct(private RequestOperator $requests) {}

    public function __invoke(Request $request, CacheableGenerator $generator): JsonResponse
    {
        if (! $this->requests->operator($request)->can(Operator::HEALTH)) {
            throw new ForbiddenException;
        }

        return new JsonResponse($generator(Scramble::getGeneratorConfig(Scramble::DEFAULT_API)), options: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
