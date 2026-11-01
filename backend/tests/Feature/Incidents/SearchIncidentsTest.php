<?php

declare(strict_types=1);

// Búsqueda del listado. Va aparte porque el índice FULLTEXT de InnoDB solo ve filas confirmadas: estas pruebas
// guardan de verdad, en lugar de revertir una transacción. DatabaseTruncation vacía las tablas antes de cada prueba,
// no después: cada una borra lo suyo al terminar para que las siguientes no lo encuentren. Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Infrastructure\Models\Incident;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;

uses(DatabaseTruncation::class);

beforeEach(function (): void {
    $this->made = [
        Incident::factory()->create(['code' => 'TZ-00-0042', 'title' => 'La impresora del piso 3 no imprime', 'description' => 'Papel atascado.']),
        Incident::factory()->create(['code' => 'TZ-00-0043', 'title' => 'La VPN se desconecta', 'description' => 'Pasa en la tarde.']),
    ];
    actingAsDemo('supervisor@trazo.test');
});

afterEach(function (): void {
    $made = collect($this->made);
    Incident::query()->withTrashed()->whereKey($made->pluck('id'))->forceDelete();
    User::query()->whereKey($made->pluck('created_by'))->delete();
});

function searchCodes(string $text): array
{
    return array_column(test()->getJson('/api/v1/incidents?search='.urlencode($text))->assertOk()->json('data'), 'code');
}

it('busca por prefijo: "impre" encuentra "impresora"', function (): void {
    expect(searchCodes('impre'))->toBe(['TZ-00-0042']);
});

it('exige todas las palabras, en el título o en la descripción', function (): void {
    expect(searchCodes('vpn tarde'))->toBe(['TZ-00-0043'])
        ->and(searchCodes('vpn papel'))->toBe([]);
});

it('reconoce un código, con o sin ceros y guion', function (): void {
    expect(searchCodes('tz-00-43'))->toBe(['TZ-00-0043'])
        ->and(searchCodes('00-42'))->toBe(['TZ-00-0042']);
});

it('ignora los operadores de búsqueda que alguien intente colar', function (): void {
    expect(searchCodes('impresora -piso'))->toBe(['TZ-00-0042'])
        ->and(searchCodes('"vpn" +(tarde)'))->toBe(['TZ-00-0043']);
});

it('pide al menos una palabra de 3 letras', function (): void {
    $this->getJson('/api/v1/incidents?search=ab')
        ->assertUnprocessable()
        ->assertJsonPath('errors.search.0.code', 'SEARCH_TOO_SHORT');
});
