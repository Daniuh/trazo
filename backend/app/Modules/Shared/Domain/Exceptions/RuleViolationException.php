<?php

declare(strict_types=1);

// Los datos tienen el formato correcto pero rompen una regla de negocio.

namespace App\Modules\Shared\Domain\Exceptions;

class RuleViolationException extends DomainException {}
