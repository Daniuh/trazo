<?php

declare(strict_types=1);

// Caso de uso: cambiar la contraseña desde Mi cuenta. Pide la actual como reconfirmación, cierra las demás sesiones
// (la actual sigue abierta) y avisa por correo. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Password;

use App\Modules\Auth\Application\Mfa\SecurityEvent;
use App\Modules\Auth\Application\Ports\SecurityNotifier;
use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Auth\Application\Ports\UserAccounts;
use App\Modules\Auth\Domain\Exceptions\PasswordExceptions;
use App\Modules\Shared\Application\Ports\SessionSignals;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;
use Psr\Log\LoggerInterface;

final readonly class ChangePassword
{
    public function __construct(
        private UserAccounts $accounts,
        private SessionTokens $tokens,
        private SecurityNotifier $notifier,
        private SessionSignals $signals,
        private LoggerInterface $logger,
    ) {}

    /**
     * @throws RuleViolationException
     */
    public function handle(int $userId, int $currentTokenId, string $current, string $new): void
    {
        if (! $this->accounts->passwordMatches($userId, $current)) {
            throw PasswordExceptions::currentPasswordInvalid();
        }
        if ($current === $new) {
            throw PasswordExceptions::sameAsCurrent();
        }

        $this->accounts->updatePassword($userId, $new);
        $this->tokens->revokeAll($userId, $currentTokenId);
        // Las demás pantallas abiertas se cierran al instante; esta sigue, porque su sesión sigue valiendo
        $this->signals->changed($userId);
        $this->notifier->notify($userId, SecurityEvent::PasswordChanged);
        $this->logger->info('Contraseña cambiada desde Mi cuenta', ['user_id' => $userId]);
    }
}
