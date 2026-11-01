<?php

declare(strict_types=1);

// Pasa de "los permisos que esta persona debe tener" a excepciones sobre su rol: concedidos (los que el rol no trae) y
// quitados (los del rol que no quedan). También dice qué cambió respecto de lo que tenía. Lógica pura.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

final readonly class PermissionChange
{
    /**
     * @param  list<string>  $grants  Concedidos a la persona, fuera de su rol
     * @param  list<string>  $denies  Quitados a la persona, aunque su rol los traiga
     * @param  list<string>  $added  Los que gana con el cambio
     * @param  list<string>  $removed  Los que pierde con el cambio
     */
    private function __construct(
        public array $grants,
        public array $denies,
        public array $added,
        public array $removed,
    ) {}

    /**
     * @param  list<string>  $rolePermissions  Los que trae su rol
     * @param  list<string>  $current  Los que tiene hoy (rol + concedidos − quitados)
     * @param  list<string>  $desired  Los que debe tener
     */
    public static function between(array $rolePermissions, array $current, array $desired): self
    {
        $desired = array_values(array_unique($desired));

        return new self(
            self::sorted(array_diff($desired, $rolePermissions)),
            self::sorted(array_diff($rolePermissions, $desired)),
            self::sorted(array_diff($desired, $current)),
            self::sorted(array_diff($current, $desired)),
        );
    }

    public function isEmpty(): bool
    {
        return $this->added === [] && $this->removed === [];
    }

    /**
     * Los que gana y quien administra no tiene: nadie concede lo que no tiene, salvo la cima.
     *
     * @return list<string>
     */
    public function notGrantableBy(Administrator $admin): array
    {
        return $admin->isRoot() ? [] : array_values(array_filter($this->added, fn (string $code): bool => ! $admin->can($code)));
    }

    // Tocar permisos de administración o de sistema exige que quien administra confirme con su código
    public function isSensitive(): bool
    {
        return SensitivePermissions::any([...$this->added, ...$this->removed]);
    }

    /**
     * @param  array<int|string, string>  $codes
     * @return list<string>
     */
    private static function sorted(array $codes): array
    {
        $codes = array_values($codes);
        sort($codes);

        return $codes;
    }
}
