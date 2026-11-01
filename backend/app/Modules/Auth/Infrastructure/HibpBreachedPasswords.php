<?php

declare(strict_types=1);

// Contraseñas filtradas: consulta Have I Been Pwned con su método anónimo (k-anonimato). Solo salen los primeros 5
// caracteres del SHA-1 de la contraseña, nunca la contraseña; el resto se compara aquí. Siempre se revisa además la
// lista local de las más comunes, que es la única defensa si el servicio no responde. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Ports\BreachedPasswords;
use App\Modules\Shared\Application\Ports\SystemEvents;
use App\Modules\Shared\Application\SystemEventType;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use Throwable;

final class HibpBreachedPasswords implements BreachedPasswords
{
    private const RANGE_URL = 'https://api.pwnedpasswords.com/range/';

    /** @var array<string, true>|null */
    private static ?array $common = null;

    public function __construct(
        private readonly Http $http,
        private readonly LoggerInterface $logger,
        private readonly SystemEvents $events,
        private readonly string $commonListPath,
        private readonly int $timeoutSeconds,
    ) {}

    public function isBreached(string $password): bool
    {
        return $this->isCommon($password) || $this->isPwned($password) === true;
    }

    // true o false según el servicio; null si no respondió (no se bloquea a nadie por eso)
    private function isPwned(string $password): ?bool
    {
        $hash = strtoupper(sha1($password));
        [$prefix, $suffix] = [substr($hash, 0, 5), substr($hash, 5)];

        try {
            // Con relleno, todas las respuestas pesan parecido: quien mire el tráfico no deduce nada del tamaño
            $response = $this->http->timeout($this->timeoutSeconds)
                ->withHeaders(['Add-Padding' => 'true'])
                ->get(self::RANGE_URL.$prefix);
        } catch (Throwable $error) {
            $this->logger->warning('Have I Been Pwned no respondió; se usa solo la lista local', ['error' => $error->getMessage()]);
            $this->unavailable(['reason' => 'timeout_or_network']);

            return null;
        }

        if (! $response->successful()) {
            $this->logger->warning('Have I Been Pwned respondió con error; se usa solo la lista local', ['status' => $response->status()]);
            $this->unavailable(['reason' => 'http_error', 'status' => $response->status()]);

            return null;
        }

        foreach (explode("\n", $response->body()) as $line) {
            [$candidate, $count] = array_pad(explode(':', trim($line)), 2, '0');
            // Las filas de relleno vienen con conteo 0
            if ($candidate === $suffix && (int) $count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, scalar>  $context
     */
    private function unavailable(array $context): void
    {
        $this->events->record(SystemEventType::ExternalServiceUnavailable, 'hibp', 'Have I Been Pwned no respondió; se revisó solo con la lista local', [
            'service' => 'Have I Been Pwned',
            ...$context,
        ]);
    }

    private function isCommon(string $password): bool
    {
        self::$common ??= $this->loadCommon();

        return isset(self::$common[mb_strtolower($password)]);
    }

    /** @return array<string, true> */
    private function loadCommon(): array
    {
        $lines = file($this->commonListPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $words = array_filter($lines, fn (string $line): bool => ! str_starts_with($line, '#'));

        return array_fill_keys($words, true);
    }
}
