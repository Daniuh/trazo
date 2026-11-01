<?php

declare(strict_types=1);

// La operación choca con el estado actual del recurso (transición inválida, versión desactualizada…).

namespace App\Modules\Shared\Domain\Exceptions;

class ConflictException extends DomainException {}
