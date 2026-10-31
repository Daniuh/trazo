<?php

declare(strict_types=1);

// Formato de los errores de la API (RFC 9457) y su identificador de petición.
// Documentado en docs/api/errores.md

use App\Modules\Shared\Domain\Exceptions\ConflictException;
use App\Modules\Shared\Domain\Exceptions\ResourceNotFoundException;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// El error 500 queda como evento del sistema: se deshace al terminar cada prueba
uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Rutas solo para pruebas, montadas bajo el mismo prefijo y grupo que la API real
    Route::middleware('api')->prefix('api/v1/_test')->group(function (): void {
        Route::post('/validation', fn (Request $r) => $r->validate([
            'title' => ['required', 'string', 'max:150'],
            'email' => ['email'],
        ]));
        Route::get('/not-found', fn () => throw new ResourceNotFoundException('INCIDENT_NOT_FOUND'));
        Route::get('/conflict', fn () => throw new ConflictException('INVALID_STATUS_TRANSITION', ['from' => 'closed', 'to' => 'open']));
        Route::get('/rule', fn () => throw new RuleViolationException('DATE_MUST_BE_FUTURE'));
        Route::get('/crash', fn () => throw new RuntimeException('SQLSTATE secreto que nunca debe salir'));
        Route::get('/ok', fn () => ['data' => 'ok']);
    });
});

it('responde los errores de validación con un código por regla y sus parámetros', function (): void {
    $this->postJson('/api/v1/_test/validation', ['title' => str_repeat('a', 151), 'email' => 'no-es-correo'])
        ->assertStatus(422)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('code', 'VALIDATION_FAILED')
        ->assertJsonPath('errors.title.0', ['code' => 'TOO_LONG', 'params' => ['max' => '150']])
        ->assertJsonPath('errors.email.0', ['code' => 'INVALID_EMAIL']);
});

it('indica el campo obligatorio que falta', function (): void {
    $this->postJson('/api/v1/_test/validation', [])
        ->assertStatus(422)
        ->assertJsonPath('errors.title.0.code', 'REQUIRED');
});

it('traduce cada tipo de excepción de negocio a su estado HTTP', function (string $path, int $status, string $code): void {
    $this->getJson("/api/v1/_test/{$path}")
        ->assertStatus($status)
        ->assertJsonPath('code', $code)
        ->assertJsonPath('status', $status);
})->with([
    'no encontrado' => ['not-found', 404, 'INCIDENT_NOT_FOUND'],
    'conflicto' => ['conflict', 409, 'INVALID_STATUS_TRANSITION'],
    'regla de negocio' => ['rule', 422, 'DATE_MUST_BE_FUTURE'],
]);

it('incluye los parámetros que necesita el mensaje', function (): void {
    $this->getJson('/api/v1/_test/conflict')
        ->assertJsonPath('params', ['from' => 'closed', 'to' => 'open']);
});

it('nunca expone detalles técnicos en un error inesperado', function (): void {
    $response = $this->getJson('/api/v1/_test/crash')
        ->assertStatus(500)
        ->assertJsonPath('code', 'INTERNAL_ERROR');

    expect($response->getContent())->not->toContain('SQLSTATE')->not->toContain('trace"');
});

it('responde en JSON una ruta inexistente aunque el cliente no pida JSON', function (): void {
    $this->get('/api/v1/no-existe')
        ->assertStatus(404)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('code', 'RESOURCE_NOT_FOUND');
});

it('responde 405 indicando los métodos permitidos', function (): void {
    $this->deleteJson('/api/v1/_test/ok')
        ->assertStatus(405)
        ->assertJsonPath('code', 'METHOD_NOT_ALLOWED')
        ->assertHeader('Allow');
});

it('genera un identificador de petición y lo devuelve en la respuesta y en el error', function (): void {
    $response = $this->getJson('/api/v1/_test/not-found');

    $id = $response->headers->get('X-Request-Id');
    expect($id)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
    $response->assertJsonPath('trace_id', $id);
});

it('reutiliza un identificador válido recibido del cliente', function (): void {
    $this->getJson('/api/v1/_test/ok', ['X-Request-Id' => 'cliente-1234-abcd'])
        ->assertOk()
        ->assertHeader('X-Request-Id', 'cliente-1234-abcd');
});

it('descarta un identificador con formato inseguro', function (): void {
    $response = $this->getJson('/api/v1/_test/ok', ['X-Request-Id' => "<script>\nalert(1)"]);

    expect($response->headers->get('X-Request-Id'))->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
});
