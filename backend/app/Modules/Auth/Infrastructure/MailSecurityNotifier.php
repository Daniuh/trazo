<?php

declare(strict_types=1);

// Avisos de seguridad por correo. La hora va en la zona del negocio, que es la que la persona reconoce.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Mfa\SecurityEvent;
use App\Modules\Auth\Application\Ports\SecurityNotifier;
use App\Modules\Auth\Infrastructure\Mail\SecurityNotice;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

final readonly class MailSecurityNotifier implements SecurityNotifier
{
    public function __construct(private string $timezone) {}

    public function notify(int $userId, SecurityEvent $event): void
    {
        $user = User::query()->findOrFail($userId, ['id', 'name', 'email']);
        $now = Carbon::now($this->timezone);
        $now->setLocale('es');
        $when = $now->translatedFormat('j \d\e F \d\e Y, h:i a');

        Mail::to($user->email)->queue(new SecurityNotice($user->name, $event, $when));
    }
}
