<?php

declare(strict_types=1);

// Convierte las reglas de validación que fallaron en códigos estables con sus parámetros.
// Documentado en docs/api/errores.md

namespace App\Modules\Shared\Http\Errors;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;

final class ValidationErrorFormatter
{
    /**
     * Regla de Laravel => [código, nombres de sus parámetros].
     *
     * @var array<string, array{0: string, 1: list<string>}>
     */
    private const RULES = [
        'Required' => ['REQUIRED', []],
        'RequiredIf' => ['REQUIRED', []],
        'RequiredWith' => ['REQUIRED', []],
        'Present' => ['REQUIRED', []],
        'Filled' => ['REQUIRED', []],
        'String' => ['MUST_BE_TEXT', []],
        'Integer' => ['MUST_BE_INTEGER', []],
        'Numeric' => ['MUST_BE_NUMBER', []],
        'Boolean' => ['MUST_BE_BOOLEAN', []],
        'Array' => ['MUST_BE_LIST', []],
        'Email' => ['INVALID_EMAIL', []],
        'Date' => ['INVALID_DATE', []],
        'DateFormat' => ['INVALID_DATE', []],
        'Ulid' => ['INVALID_IDENTIFIER', []],
        'Min' => ['TOO_SHORT', ['min']],
        'Max' => ['TOO_LONG', ['max']],
        'Between' => ['OUT_OF_RANGE', ['min', 'max']],
        'Size' => ['WRONG_SIZE', ['size']],
        'In' => ['INVALID_OPTION', []],
        'Exists' => ['INVALID_REFERENCE', []],
        'Unique' => ['ALREADY_TAKEN', []],
        'Confirmed' => ['CONFIRMATION_MISMATCH', []],
        'After' => ['DATE_TOO_EARLY', ['date']],
        'AfterOrEqual' => ['DATE_TOO_EARLY', ['date']],
        'Before' => ['DATE_TOO_LATE', ['date']],
        'BeforeOrEqual' => ['DATE_TOO_LATE', ['date']],
        'Prohibited' => ['NOT_ALLOWED', []],
    ];

    /**
     * @return array<string, list<array{code: string, params?: array<string, scalar>}>>
     */
    public function format(Validator $validator): array
    {
        $errors = [];

        foreach ($validator->failed() as $field => $rules) {
            foreach ($rules as $rule => $values) {
                $errors[$field][] = $this->describe((string) $rule, $values);
            }
        }

        return $errors;
    }

    /**
     * @param  array<int, scalar>  $values
     * @return array{code: string, params?: array<string, scalar>}
     */
    private function describe(string $rule, array $values): array
    {
        // Reglas propias: si saben su código, se usa tal cual
        if (is_a($rule, HasErrorCode::class, true)) {
            return ['code' => app($rule)->errorCode()];
        }

        [$code, $names] = self::RULES[$rule]
            ?? [Str::of(class_basename($rule))->snake()->upper()->toString(), []];

        $params = array_combine($names, array_slice($values, 0, count($names)));

        return $params === [] ? ['code' => $code] : ['code' => $code, 'params' => $params];
    }
}
