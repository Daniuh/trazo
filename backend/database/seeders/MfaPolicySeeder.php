<?php

declare(strict_types=1);

// Política de MFA inicial: obligatorio para todos los roles, con un ingreso en que se puede posponer.
// Idempotente y no destructivo: si un administrador ajusta la política de un rol, volver a sembrar no la revierte.
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertsMissingRows;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MfaPolicySeeder extends Seeder
{
    use InsertsMissingRows;

    private const GRACE_LOGINS = 1;

    public function run(): void
    {
        $rows = DB::table('roles')->pluck('id')->map(fn (mixed $id): array => [
            'role_id' => (int) $id,
            'is_required' => true,
            'grace_logins' => self::GRACE_LOGINS,
        ])->values()->all();

        $this->insertMissing('mfa_policies', 'role_id', $rows);
    }
}
