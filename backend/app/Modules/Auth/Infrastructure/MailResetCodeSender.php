<?php

declare(strict_types=1);

// Envía el código de recuperación por correo, por la cola. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Password\Account;
use App\Modules\Auth\Application\Ports\ResetCodeSender;
use App\Modules\Auth\Infrastructure\Mail\PasswordResetCode;
use Illuminate\Support\Facades\Mail;

final class MailResetCodeSender implements ResetCodeSender
{
    public function send(Account $account, string $code, int $minutes): void
    {
        Mail::to($account->email)->queue(new PasswordResetCode($account->name, $code, $minutes));
    }
}
