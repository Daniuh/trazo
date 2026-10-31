<?php

declare(strict_types=1);

// Endpoints del MFA: verificar el segundo paso del ingreso, configurarlo, regenerar códigos y desactivarlo.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use App\Modules\Auth\Application\Mfa\ConfirmMfaSetup;
use App\Modules\Auth\Application\Mfa\DisableMfa;
use App\Modules\Auth\Application\Mfa\RegenerateRecoveryCodes;
use App\Modules\Auth\Application\Mfa\StartMfaSetup;
use App\Modules\Auth\Application\Mfa\VerifyMfa;
use App\Modules\Auth\Domain\Exceptions\MfaExceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class MfaController
{
    use ResolvesSessionUser;

    public function __construct(private readonly SessionPayload $payload) {}

    public function verify(VerifyMfaRequest $request, VerifyMfa $verify): JsonResponse
    {
        if (! $this->isChallenge($request)) {
            throw MfaExceptions::notPending();
        }

        $session = $verify->handle($this->user($request)->id, $this->tokenId($request), $request->code(), $request->recoveryCode());

        return new JsonResponse(['data' => $this->payload->from($session)]);
    }

    public function start(Request $request, StartMfaSetup $start): JsonResponse
    {
        $user = $this->user($request);
        $setup = $start->handle($user->id, $user->email);

        // La clave sale solo aquí, una vez, para escanearla o escribirla en la aplicación
        return new JsonResponse(['data' => ['secret' => $setup->secret, 'uri' => $setup->uri]]);
    }

    public function confirm(MfaCodeRequest $request, ConfirmMfaSetup $confirm): JsonResponse
    {
        $challenge = $this->isChallenge($request) ? $this->tokenId($request) : null;
        $result = $confirm->handle($this->user($request)->id, $request->code(), $challenge);

        return new JsonResponse(['data' => [
            'recovery_codes' => $result->recoveryCodes,
            'session' => $result->session === null ? null : $this->payload->from($result->session),
        ]]);
    }

    public function regenerate(MfaCodeRequest $request, RegenerateRecoveryCodes $regenerate): JsonResponse
    {
        return new JsonResponse(['data' => [
            'recovery_codes' => $regenerate->handle($this->user($request)->id, $request->code()),
        ]]);
    }

    public function disable(MfaCodeRequest $request, DisableMfa $disable): Response
    {
        $disable->handle($this->user($request)->id, $request->code());

        return response()->noContent();
    }
}
