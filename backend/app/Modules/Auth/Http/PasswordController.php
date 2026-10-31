<?php

declare(strict_types=1);

// Endpoints de contraseña: pedir un código de recuperación, verificarlo, elegir la nueva y cambiarla desde Mi cuenta.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use App\Modules\Auth\Application\Password\ChangePassword;
use App\Modules\Auth\Application\Password\RequestPasswordReset;
use App\Modules\Auth\Application\Password\ResetPassword;
use App\Modules\Auth\Application\Password\VerifyResetCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class PasswordController
{
    use ResolvesSessionUser;

    // 202 siempre, exista o no el correo: la respuesta no revela qué cuentas existen
    public function forgot(ForgotPasswordRequest $request, RequestPasswordReset $reset): Response
    {
        $reset->handle($request->email());

        return response()->noContent(202);
    }

    public function verifyCode(VerifyResetCodeRequest $request, VerifyResetCode $verify): JsonResponse
    {
        $ticket = $verify->handle($request->email(), $request->code());

        return new JsonResponse(['data' => ['ticket' => $ticket->ticket, 'mfa_required' => $ticket->mfaRequired]]);
    }

    public function reset(ResetPasswordRequest $request, ResetPassword $reset): Response
    {
        $reset->handle(
            $request->email(),
            $request->ticket(),
            $request->newPassword(),
            $request->mfaCode(),
            $request->recoveryCode(),
        );

        return response()->noContent();
    }

    public function change(ChangePasswordRequest $request, ChangePassword $change): Response
    {
        $change->handle($this->user($request)->id, $this->tokenId($request), $request->currentPassword(), $request->newPassword());

        return response()->noContent();
    }
}
