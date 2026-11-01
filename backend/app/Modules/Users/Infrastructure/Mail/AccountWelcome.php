<?php

declare(strict_types=1);

// Correo de bienvenida a una cuenta nueva: cómo crear la contraseña e ingresar. No lleva contraseñas ni enlaces con
// códigos: la persona pide su propio código desde la pantalla de ingreso. Sale por la cola, cifrado.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class AccountWelcome extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
        public readonly string $createPasswordUrl,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Te damos la bienvenida a Trazo');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.account-welcome', with: [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'url' => $this->createPasswordUrl,
        ]);
    }
}
