<?php

declare(strict_types=1);

// Códigos de recuperación: 10 caracteres en dos grupos (ABCDE-FGHJK), sin letras que se confunden (0/O, 1/I/L).
// Son aleatorios y largos (unos 50 bits), así que basta un hash rápido. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Mfa;

final class RecoveryCode
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const GROUP = 5;

    public static function generate(): string
    {
        $chars = '';
        for ($i = 0; $i < self::GROUP * 2; $i++) {
            $chars .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return substr($chars, 0, self::GROUP).'-'.substr($chars, self::GROUP);
    }

    /**
     * Un juego de códigos distintos entre sí.
     *
     * @return list<string>
     */
    public static function batch(int $count): array
    {
        $codes = [];
        while (count($codes) < $count) {
            $codes[self::generate()] = true;
        }

        return array_keys($codes);
    }

    // Acepta minúsculas, espacios y el guion en cualquier lugar: lo que importa son los 10 caracteres
    public static function normalize(string $input): string
    {
        $chars = (string) preg_replace('/[^A-Z0-9]/', '', strtoupper($input));

        return strlen($chars) === self::GROUP * 2 ? substr($chars, 0, self::GROUP).'-'.substr($chars, self::GROUP) : $chars;
    }

    public static function hash(string $input): string
    {
        return hash('sha256', self::normalize($input));
    }
}
