<?php

declare(strict_types=1);

// Cambiar el estado de un evento: { status: open | acknowledged | resolved }. Documentado en docs/backend/system.md

namespace App\Modules\System\Http\Requests;

use App\Modules\System\Domain\EventStatus;
use App\Modules\System\Domain\Operator;
use App\Modules\Users\Http\Requests\ChecksPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EventStatusRequest extends FormRequest
{
    use ChecksPermission;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['status' => ['required', 'string', Rule::enum(EventStatus::class)]];
    }

    public function status(): EventStatus
    {
        return EventStatus::from($this->string('status')->toString());
    }

    protected function requiredPermission(): string
    {
        return Operator::LOGS;
    }
}
