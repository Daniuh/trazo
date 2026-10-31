<?php

declare(strict_types=1);

// El recurso no existe o quien consulta no puede verlo (para la API ambos casos son iguales).

namespace App\Modules\Shared\Domain\Exceptions;

class ResourceNotFoundException extends DomainException {}
