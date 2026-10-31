<?php

declare(strict_types=1);

// Patrones que hacen predecible una contraseña larga: repetición, secuencias y datos personales o del producto.
// Documentado en docs/backend/auth.md

use App\Modules\Auth\Domain\Password\PasswordWeakness;
use App\Modules\Auth\Domain\Password\WeakPasswordPatterns;

it('rechaza las contraseñas con pocos caracteres distintos o un bloque repetido', function (string $password): void {
    expect(WeakPasswordPatterns::weakness($password))->toBe(PasswordWeakness::Repetitive);
})->with(['ddddddddddddddddd', 'abababababab', 'aAaAaAaAaAaA', 'hola1hola1hola1', 'Correcto!Correcto!']);

it('rechaza las secuencias del alfabeto, los números o el teclado', function (string $password): void {
    expect(WeakPasswordPatterns::weakness($password))->toBe(PasswordWeakness::Sequential);
})->with(['abcdefghijkl', '123456789012', 'qwertyuiopas', '9876543210xy', 'zyxwvutsrqpo', 'asdfghjklñ12']);

it('rechaza las que contienen el correo, el nombre o la marca', function (string $password): void {
    expect(WeakPasswordPatterns::weakness($password, ['natalia.vargas@trazo.test', 'Natalia Vargas']))
        ->toBe(PasswordWeakness::Personal);
})->with(['MiClaveNatalia#77', 'vargas-del-sur-2026', 'natalia.vargas.99', 'Trazo es lo mejor', 'equipo psicol 2026']);

it('ignora las tildes y las mayúsculas al buscar datos personales', function (): void {
    expect(WeakPasswordPatterns::weakness('soy sofia de bogota', ['Sofía Ramírez']))->toBe(PasswordWeakness::Personal);
});

it('acepta contraseñas largas y variadas, aunque tengan algún número seguido', function (string $password): void {
    expect(WeakPasswordPatterns::weakness($password, ['ana@empresa.co', 'Ana Ruiz']))->toBeNull();
})->with(['caballo bateria grapa correcta', 'MiCasa1234Grande', 'tres-tigres-tristes', 'Lluvia en Manizales 7']);

it('no busca pedazos muy cortos del nombre', function (): void {
    // "ana" está dentro de "banana": con nombres de menos de 4 letras no se rechaza
    expect(WeakPasswordPatterns::weakness('banana con leche fria', ['Ana Ruiz']))->toBeNull();
});
