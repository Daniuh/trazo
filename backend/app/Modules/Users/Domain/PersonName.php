<?php

declare(strict_types=1);

// Nombre de una persona: nombres y apellidos por separado (cada uno puede tener una o varias palabras) y el nombre
// completo que se muestra. La columna calculada users.name aplica la misma regla en MySQL (ADR-0086).
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

final readonly class PersonName
{
    public const MAX_PART = 80;

    public string $givenNames;

    public string $familyNames;

    public function __construct(string $givenNames, string $familyNames)
    {
        $this->givenNames = self::clean($givenNames);
        $this->familyNames = self::clean($familyNames);
    }

    // "María José" + "De la Torre Gómez" = "María José De la Torre Gómez"
    public function full(): string
    {
        return trim($this->givenNames.' '.$this->familyNames);
    }

    public function equals(self $other): bool
    {
        return $this->givenNames === $other->givenNames && $this->familyNames === $other->familyNames;
    }

    // Sin espacios al inicio, al final ni repetidos entre palabras
    public static function clean(string $part): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $part));
    }
}
