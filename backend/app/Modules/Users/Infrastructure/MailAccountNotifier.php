<?php

declare(strict_types=1);

// Avisos por correo a la persona administrada. La hora va en la zona del negocio y la dirección para crear la
// contraseña sale de la URL pública de la aplicación. Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure;

use App\Modules\Users\Application\AccountEvent;
use App\Modules\Users\Application\Ports\AccountNotifier;
use App\Modules\Users\Infrastructure\Mail\AccountNotice;
use App\Modules\Users\Infrastructure\Mail\AccountWelcome;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

final readonly class MailAccountNotifier implements AccountNotifier
{
    // La pantalla de "¿Olvidaste tu contraseña?", que también sirve para crear la primera
    private const CREATE_PASSWORD_PATH = '/recuperar';

    public function __construct(
        private string $appUrl,
        private string $timezone,
    ) {}

    public function welcome(int $userId): void
    {
        $user = User::query()->with('roles:id,name')->findOrFail($userId, ['id', 'name', 'email']);
        $role = (string) $user->roles->pluck('name')->first();

        Mail::to($user->email)->queue(new AccountWelcome($user->name, $user->email, $role, $this->createPasswordUrl()));
    }

    public function notify(int $userId, AccountEvent $event, array $params = []): void
    {
        $user = User::query()->findOrFail($userId, ['id', 'name', 'email']);

        Mail::to($user->email)->queue(new AccountNotice(
            $user->name,
            $event,
            $this->now(),
            [...$params, 'url' => $this->createPasswordUrl()],
        ));
    }

    public function emailChanged(string $previousEmail, string $name): void
    {
        Mail::to($previousEmail)->queue(new AccountNotice($name, AccountEvent::EmailChanged, $this->now(), []));
    }

    private function createPasswordUrl(): string
    {
        return rtrim($this->appUrl, '/').self::CREATE_PASSWORD_PATH;
    }

    private function now(): string
    {
        $now = Carbon::now($this->timezone);
        $now->setLocale('es');

        return $now->translatedFormat('j \d\e F \d\e Y, h:i a');
    }
}
