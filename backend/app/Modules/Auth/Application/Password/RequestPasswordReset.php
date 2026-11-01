<?php

declare(strict_types=1);

// Caso de uso: pedir un código para recuperar la contraseña. Si el correo es de una cuenta activa, le llega un código
// de 6 dígitos que vence en pocos minutos y anula los anteriores. Si no, no pasa nada: la respuesta es la misma, para
// no revelar qué correos existen. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Password;

use App\Modules\Auth\Application\Ports\PasswordResets;
use App\Modules\Auth\Application\Ports\ResetCodeSender;
use App\Modules\Auth\Application\Ports\UserAccounts;
use App\Modules\Auth\Domain\Password\ResetCode;
use Carbon\CarbonImmutable;

final readonly class RequestPasswordReset
{
    public function __construct(
        private UserAccounts $accounts,
        private PasswordResets $resets,
        private ResetCodeSender $sender,
        private int $codeMinutes,
    ) {}

    public function handle(string $email): void
    {
        $account = $this->accounts->findActiveByEmail($email);
        if ($account === null) {
            return;
        }

        $code = ResetCode::generate();
        $this->resets->start($account->id, $code, CarbonImmutable::now()->addMinutes($this->codeMinutes));
        $this->sender->send($account, $code, $this->codeMinutes);
    }
}
