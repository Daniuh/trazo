<?php

declare(strict_types=1);

// Código legible de un incidente: prefijo, año y consecutivo del año (TZ-26-0142). Cuatro cifras como mínimo; desde
// el 10.000 del año el número sigue creciendo sin cortarse. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

final readonly class IncidentCode
{
    // Lo que una persona escribe para buscarlo: "TZ-26-0142", "tz26-142" o solo "26-0142". El año va siempre,
    // porque el número se repite cada año; el guion entre año y número evita confundirlo con una cifra cualquiera
    private const PATTERN = '/^(?:[A-Z]{2,4}-?)?(\d{2})-(\d{1,7})$/i';

    public static function format(string $prefix, int $year, int $number): string
    {
        return sprintf('%s-%02d-%04d', strtoupper($prefix), $year % 100, $number);
    }

    public static function looksLike(string $text): bool
    {
        return self::normalize($text, 'XX') !== null;
    }

    // El código completo que corresponde a lo escrito, o null si no parece un código
    public static function normalize(string $text, string $prefix): ?string
    {
        if (preg_match(self::PATTERN, trim($text), $match) !== 1 || (int) $match[2] === 0) {
            return null;
        }

        return self::format($prefix, (int) $match[1], (int) $match[2]);
    }
}
