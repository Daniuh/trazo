<?php

declare(strict_types=1);

// Caso de uso: comprobar el código que llegó al correo. Si es correcto, lo cambia por un pase de un solo uso para
// elegir la contraseña nueva. Un código mal escrito, vencido o con los intentos agotados da el mismo error, igual que
// un correo que no existe. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Password;

use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\PasswordResets;
use App\Modules\Auth\Application\Ports\UserAccounts;
use App\Modules\Auth\Domain\Exceptions\PasswordExceptions;
use App\Modules\Auth\Domain\Password\ResetCode;
use App\Modules\Shared\Application\Ports\SystemEvents;
use App\Modules\Shared\Application\SystemEventType;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;

final readonly class VerifyResetCode
{
    public function __construct(
        private UserAccounts $accounts,
        private PasswordResets $resets,
        private MfaStore $mfa,
        private LoggerInterface $logger,
        private SystemEvents $events,
        private int $maxAttempts,
        private int $ticketMinutes,
    ) {}

    /**
     * @throws RuleViolationException
     */
    public function handle(string $email, string $code): ResetTicket
    {
        $now = CarbonImmutable::now();
        $account = $this->accounts->findActiveByEmail($email);
        $reset = $account === null ? null : $this->resets->current($account->id);

        if ($account === null || $reset === null || ! $reset->codeIsUsable($now, $this->maxAttempts)) {
            throw PasswordExceptions::invalidResetCode();
        }

        if (! $this->resets->matchesCode($reset, $code)) {
            if ($this->resets->recordFailure($reset) >= $this->maxAttempts) {
                $this->resets->close($reset);
                $this->logger->warning('Código de recuperación anulado por intentos fallidos', ['user_id' => $account->id]);
                $this->events->record(SystemEventType::ResetCodeVoided, "user:{$account->id}", 'Código de recuperación de contraseña anulado por intentos fallidos', [
                    'user_id' => $account->id,
                ]);
            }

            throw PasswordExceptions::invalidResetCode();
        }

        $ticket = ResetCode::ticket();
        $this->resets->issueTicket($reset, $ticket, $now->addMinutes($this->ticketMinutes));

        return new ResetTicket($ticket, $this->mfa->find($account->id)?->confirmed === true);
    }
}
