<?php

declare(strict_types=1);

// Búsqueda del listado. Va aparte porque el índice FULLTEXT de InnoDB solo ve filas confirmadas: estas pruebas
// guardan de verdad y vacían las tablas al terminar, en lugar de revertir una transacción. Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Infrastructure\Models\Incident;
use Illuminate\Foundation\Testing\DatabaseTruncation;

uses(DatabaseTruncation::class);

beforeEach(function (): void {
    Incident::factory()->create(['code' => 'INC-000042', 'title' => 'La impresora del piso 3 no imprime', 'description' => 'Papel atascado.']);
    Incident::factory()->create(['code' => 'INC-000043', 'title' => 'La VPN se desconecta', 'description' => 'Pasa en la tarde.']);
    actingAsDemo('supervisor@trazo.test');
});

function searchCodes(string $text): array
{
    return array_column(test()->getJson('/api/v1/incidents?search='.urlencode($text))->assertOk()->json('data'), 'code');
}

it('busca por prefijo: "impre" encuentra "impresora"', function (): void {
    expect(searchCodes('impre'))->toBe(['INC-000042']);
});

it('exige todas las palabras, en el título o en la descripción', function (): void {
    expect(searchCodes('vpn tarde'))->toBe(['INC-000043'])
        ->and(searchCodes('vpn papel'))->toBe([]);
});

it('reconoce un código, con o sin ceros y guion', function (): void {
    expect(searchCodes('inc-43'))->toBe(['INC-000043'])
        ->and(searchCodes('INC000042'))->toBe(['INC-000042']);
});

it('ignora los operadores de búsqueda que alguien intente colar', function (): void {
    expect(searchCodes('impresora -piso'))->toBe(['INC-000042'])
        ->and(searchCodes('"vpn" +(tarde)'))->toBe(['INC-000043']);
});

it('pide al menos una palabra de 3 letras', function (): void {
    $this->getJson('/api/v1/incidents?search=ab')
        ->assertUnprocessable()
        ->assertJsonPath('errors.search.0.code', 'SEARCH_TOO_SHORT');
});
