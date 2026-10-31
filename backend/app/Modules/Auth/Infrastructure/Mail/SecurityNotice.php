<?php

declare(strict_types=1);

// Correo de aviso por un cambio de seguridad en la cuenta. Va en texto plano, sin enlaces ni datos sensibles: solo
// cuenta qué pasó, cuándo y qué hacer si no fue la persona. Sale por la cola, después de confirmar la transacción.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure\Mail;

use App\Modules\Auth\Application\Mfa\SecurityEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class SecurityNotice extends Mailable implements ShouldQueue
{
    use Queueable;

    private const TEXTS = [
        'mfa_enabled' => [
            'Activaste la verificación en dos pasos',
            'Desde ahora, además de tu contraseña, Trazo te pedirá el código de tu aplicación de autenticación al ingresar.',
        ],
        'mfa_disabled' => [
            'Desactivaste la verificación en dos pasos',
            'Tu cuenta ya no pide el código de la aplicación de autenticación al ingresar.',
        ],
        'recovery_code_used' => [
            'Se usó un código de recuperación en tu cuenta',
            'Alguien ingresó a tu cuenta con uno de tus códigos de recuperación. Ese código ya no sirve.',
        ],
        'recovery_codes_regenerated' => [
            'Generaste códigos de recuperación nuevos',
            'Los códigos de recuperación anteriores dejaron de servir. Guarda los nuevos en un lugar seguro.',
        ],
    ];

    public function __construct(
        public readonly string $name,
        public readonly SecurityEvent $event,
        public readonly string $happenedAt,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::TEXTS[$this->event->value][0]);
    }

    public function content(): Content
    {
        return new Content(text: 'mail.security-notice', with: [
            'name' => $this->name,
            'title' => self::TEXTS[$this->event->value][0],
            'body' => self::TEXTS[$this->event->value][1],
            'happenedAt' => $this->happenedAt,
        ]);
    }
}
