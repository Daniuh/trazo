<?php

declare(strict_types=1);

// Qué parte del registro de administración ve alguien. La misma regla de "solo hacia abajo" de la administración:
// sus propias acciones y las que tocan a una persona o un rol de un nivel menor al suyo. El nivel es el de hoy: si
// alguien sube de rol, sus registros pasados quedan para quien está por encima. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Domain;

final readonly class AdminAuditScope
{
    private function __construct(
        public bool $everything,
        public int $viewerId,
        public int $level,
    ) {}

    public static function everything(): self
    {
        return new self(true, 0, 0);
    }

    public static function below(int $viewerId, int $level): self
    {
        return new self(false, $viewerId, $level);
    }

    // $targetLevel es el nivel de la persona o el rol afectado; null si la acción no tiene a quién apuntar
    public function includes(int $actorId, ?int $targetLevel): bool
    {
        return $this->everything
            || $actorId === $this->viewerId
            || ($targetLevel !== null && $targetLevel < $this->level);
    }
}
