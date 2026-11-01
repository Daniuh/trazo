<?php

declare(strict_types=1);

// Base de las peticiones de sistema: el permiso se revisa antes de validar y el código de reautenticación llega en
// mfa_code (como en la administración de usuarios). Documentado en docs/backend/system.md

namespace App\Modules\System\Http\Requests;

use App\Modules\System\Domain\Operator;
use App\Modules\Users\Http\Requests\ChecksPermission;
use Illuminate\Foundation\Http\FormRequest;

abstract class SystemRequest extends FormRequest
{
    use ChecksPermission;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['mfa_code' => ['sometimes', 'nullable', 'string', 'max:20'], ...$this->extraRules()];
    }

    public function mfaCode(): ?string
    {
        return $this->filled('mfa_code') ? $this->string('mfa_code')->toString() : null;
    }

    protected function requiredPermission(): string
    {
        return Operator::ACTIONS;
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function extraRules(): array
    {
        return [];
    }
}
