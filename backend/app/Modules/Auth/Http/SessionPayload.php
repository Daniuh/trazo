<?php

declare(strict_types=1);

// Respuesta de un ingreso: el token, hasta cuándo vale y qué paso sigue. Si el paso es del MFA, no trae los datos de
// la persona: el token todavía no abre la sesión. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use App\Modules\Auth\Application\IssuedSession;
use App\Modules\Auth\Application\Ports\SessionUserReader;
use DateTimeInterface;

final readonly class SessionPayload
{
    public function __construct(private SessionUserReader $users) {}

    /**
     * @return array{status: string, token: string, token_type: string, expires_at: string, user: array<string, mixed>|null}
     */
    public function from(IssuedSession $session): array
    {
        return [
            'status' => $session->step->value,
            'token' => $session->token,
            'token_type' => 'Bearer',
            'expires_at' => $session->expiresAt->format(DateTimeInterface::ATOM),
            'user' => $session->step->grantsSession() ? SessionUserResource::from($this->users->find($session->userId)) : null,
        ];
    }
}
