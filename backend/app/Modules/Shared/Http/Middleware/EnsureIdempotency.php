<?php

declare(strict_types=1);

// Idempotency-Key: si el mismo pedido llega dos veces (doble clic, reintento por red), se responde lo mismo
// sin repetir la escritura. Reutilizar la clave con otros datos es un error. Documentado en docs/api/idempotencia.md

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Domain\Exceptions\ConflictException;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;
use Closure;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureIdempotency
{
    private const HEADER = 'Idempotency-Key';

    private const KEY_FORMAT = '/^[A-Za-z0-9-]{8,64}$/';

    private const TTL_SECONDS = 86400;

    public function __construct(private Repository $cache) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header(self::HEADER);
        if ($key === null) {
            return $next($request);
        }
        if (! preg_match(self::KEY_FORMAT, $key)) {
            throw new RuleViolationException('INVALID_IDEMPOTENCY_KEY');
        }

        // La clave es por persona y por ruta: nadie puede chocar con la de otro
        $scope = 'idempotency:'.hash('sha256', $request->user()?->getAuthIdentifier().'|'.$request->path().'|'.$key);
        $fingerprint = hash('sha256', (string) $request->getContent());

        $stored = $this->cache->get($scope);
        if (is_array($stored)) {
            return $this->replay($stored, $fingerprint);
        }

        $store = $this->cache->getStore();
        if (! $store instanceof LockProvider) {
            throw new LogicException('La caché configurada no admite bloqueos y la idempotencia los necesita.');
        }
        $lock = $store->lock($scope.':lock', 10);
        if (! $lock->get()) {
            throw new ConflictException('IDEMPOTENCY_IN_PROGRESS');
        }

        try {
            $response = $next($request);
            // Solo se recuerdan los éxitos: un error se puede corregir y reintentar con la misma clave
            if ($response instanceof JsonResponse && $response->isSuccessful()) {
                $this->cache->put($scope, [
                    'fingerprint' => $fingerprint,
                    'status' => $response->getStatusCode(),
                    'body' => $response->getData(true),
                ], self::TTL_SECONDS);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, mixed>  $stored
     */
    private function replay(array $stored, string $fingerprint): JsonResponse
    {
        if ($stored['fingerprint'] !== $fingerprint) {
            throw new ConflictException('IDEMPOTENCY_KEY_REUSED');
        }

        return new JsonResponse($stored['body'], (int) $stored['status'], ['Idempotent-Replayed' => 'true']);
    }
}
