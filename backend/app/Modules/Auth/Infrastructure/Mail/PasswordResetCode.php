<?php

declare(strict_types=1);

// Correo con el código para recuperar la contraseña. Texto plano, sin enlaces: solo el código y cuánto dura.
// Sale por la cola. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class PasswordResetCode extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly int $minutes,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu código para recuperar la contraseña de Trazo');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.password-reset-code', with: [
            'name' => $this->name,
            'code' => $this->code,
            'minutes' => $this->minutes,
        ]);
    }
}
