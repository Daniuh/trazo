<?php

declare(strict_types=1);

// Correo de aviso por un cambio que la administración hizo en la cuenta. Texto plano, sin datos sensibles: qué pasó,
// cuándo y, si hace falta, qué hacer. Sale por la cola, cifrado, después de confirmar la transacción.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure\Mail;

use App\Modules\Users\Application\AccountEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class AccountNotice extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    // Asunto, qué pasó y qué hacer (vacío si no hay nada que hacer). {role} y {url} se reemplazan
    private const TEXTS = [
        'activated' => [
            'Tu cuenta de Trazo está activa de nuevo',
            'Ya puedes ingresar a Trazo con tu correo y tu contraseña.',
            '',
        ],
        'deactivated' => [
            'Tu cuenta de Trazo fue desactivada',
            'Ya no puedes ingresar a Trazo y se cerraron tus sesiones abiertas.',
            '',
        ],
        'role_changed' => [
            'Tu rol en Trazo cambió',
            'Ahora tu rol es {role}. Lo que puedes ver y hacer en Trazo cambia según ese rol.',
            '',
        ],
        'permissions_changed' => [
            'Tus permisos en Trazo cambiaron',
            'Cambiaron los permisos de tu cuenta: lo que puedes ver o hacer en Trazo puede ser distinto desde ahora.',
            '',
        ],
        'mfa_reset' => [
            'Se restableció tu verificación en dos pasos',
            'Se quitó la verificación en dos pasos de tu cuenta y se cerraron tus sesiones abiertas.',
            'La próxima vez que ingreses, Trazo te pedirá configurarla de nuevo con tu aplicación de autenticación.',
        ],
        'access_reset' => [
            'Se restableció el acceso a tu cuenta de Trazo',
            'Tu contraseña anterior dejó de servir y se cerraron tus sesiones abiertas.',
            'Para volver a ingresar, crea una contraseña nueva en {url} con tu correo.',
        ],
        'email_changed' => [
            'El correo de tu cuenta de Trazo cambió',
            'Tu cuenta ya no usa esta dirección: desde ahora ingresas con el correo nuevo, y allí llegan los avisos y los códigos.',
            '',
        ],
    ];

    /**
     * @param  array<string, string>  $params
     */
    public function __construct(
        public readonly string $name,
        public readonly AccountEvent $event,
        public readonly string $happenedAt,
        public readonly array $params,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::TEXTS[$this->event->value][0]);
    }

    public function content(): Content
    {
        [$title, $body, $next] = self::TEXTS[$this->event->value];
        $replace = fn (string $text): string => strtr($text, array_combine(
            array_map(fn (string $key): string => '{'.$key.'}', array_keys($this->params)),
            array_values($this->params),
        ));

        return new Content(text: 'mail.account-notice', with: [
            'name' => $this->name,
            'title' => $title,
            'body' => $replace($body),
            'next' => $replace($next),
            'happenedAt' => $this->happenedAt,
        ]);
    }
}
