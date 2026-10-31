<?php

declare(strict_types=1);

// No se pudo comprobar quién es la persona (credenciales o código incorrectos).
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Domain\Exceptions;

class AuthenticationFailedException extends DomainException {}
