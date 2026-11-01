<?php

declare(strict_types=1);

// Patrones que hacen fácil de adivinar una contraseña aunque sea larga (ADR-0070): pocos caracteres distintos, un bloque
// repetido, secuencias del alfabeto, los números o el teclado, y datos de la persona o del producto. Sigue la guía del
// NIST SP 800-63B: se bloquea lo predecible, sin exigir mayúsculas ni símbolos. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Password;

final class WeakPasswordPatterns
{
    // Menos de estos caracteres distintos es demasiado repetitivo ("dddddddddddd", "abababababab")
    public const MIN_DISTINCT_CHARACTERS = 5;

    // Una secuencia cuenta desde este largo ("1234", "qwer")
    public const MIN_SEQUENCE_LENGTH = 4;

    // Si las secuencias ocupan esta proporción o más, la contraseña es predecible
    public const MAX_SEQUENCE_SHARE = 0.5;

    // Pedazos de un dato personal más cortos que esto no se buscan ("ana" está dentro de muchas palabras)
    public const MIN_PERSONAL_TOKEN_LENGTH = 4;

    // Palabras del producto que no deben ser la base de una contraseña
    public const PRODUCT_WORDS = ['trazo', 'psicol'];

    // Alfabeto, números y filas del teclado en español, en el orden en que se recorren
    private const SEQUENCES = ['abcdefghijklmnñopqrstuvwxyz', '01234567890', 'qwertyuiop', 'asdfghjklñ', 'zxcvbnm'];

    /**
     * Devuelve la primera debilidad encontrada o null si no tiene ninguna.
     *
     * @param  list<string>  $personal  datos de la persona (correo, nombre) que no deben estar en la contraseña
     */
    public static function weakness(string $password, array $personal = []): ?PasswordWeakness
    {
        $normalized = self::normalize($password);

        return match (true) {
            self::isRepetitive($password) => PasswordWeakness::Repetitive,
            self::isSequential($normalized) => PasswordWeakness::Sequential,
            self::containsPersonalData($normalized, $personal) => PasswordWeakness::Personal,
            default => null,
        };
    }

    private static function isRepetitive(string $password): bool
    {
        $characters = mb_str_split(mb_strtolower($password));

        return count(array_unique($characters)) < self::MIN_DISTINCT_CHARACTERS
            || preg_match('/^(.+?)\1+$/us', $password) === 1;
    }

    private static function isSequential(string $normalized): bool
    {
        $characters = mb_str_split($normalized);
        $total = count($characters);
        if ($total === 0) {
            return false;
        }

        // Se suman los tramos de al menos MIN_SEQUENCE_LENGTH caracteres que avanzan en un mismo sentido
        $inSequence = 0;
        $run = 1;
        $direction = 0;
        for ($i = 1; $i < $total; $i++) {
            $step = self::step($characters[$i - 1], $characters[$i]);
            if ($step !== 0 && ($run === 1 || $step === $direction)) {
                $run++;
            } else {
                $inSequence += $run >= self::MIN_SEQUENCE_LENGTH ? $run : 0;
                $run = $step === 0 ? 1 : 2;
            }
            $direction = $step;
        }
        $inSequence += $run >= self::MIN_SEQUENCE_LENGTH ? $run : 0;

        return min($inSequence, $total) / $total >= self::MAX_SEQUENCE_SHARE;
    }

    // 1 si va hacia adelante en alguna secuencia, -1 si va hacia atrás, 0 si no son vecinos
    private static function step(string $previous, string $current): int
    {
        foreach (self::SEQUENCES as $sequence) {
            if (str_contains($sequence, $previous.$current)) {
                return 1;
            }
            if (str_contains($sequence, $current.$previous)) {
                return -1;
            }
        }

        return 0;
    }

    /**
     * @param  list<string>  $personal
     */
    private static function containsPersonalData(string $normalized, array $personal): bool
    {
        foreach ([...self::PRODUCT_WORDS, ...self::personalTokens($personal)] as $token) {
            if (str_contains($normalized, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Partes útiles de cada dato: del correo, lo que va antes de la @ y sus pedazos; del nombre, cada palabra.
     *
     * @param  list<string>  $personal
     * @return list<string>
     */
    private static function personalTokens(array $personal): array
    {
        $tokens = [];
        foreach ($personal as $value) {
            $value = self::normalize(explode('@', $value)[0]);
            $tokens = [...$tokens, $value, ...(preg_split('/[^\p{L}\p{N}]+/u', $value) ?: [])];
        }

        return array_values(array_unique(array_filter(
            $tokens,
            fn (string $token): bool => mb_strlen($token) >= self::MIN_PERSONAL_TOKEN_LENGTH,
        )));
    }

    // Minúsculas y sin tildes, para que "Sofía" y "sofia" cuenten igual
    private static function normalize(string $value): string
    {
        $lower = mb_strtolower(trim($value));

        return strtr($lower, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
    }
}
