<?php

declare(strict_types=1);

// Modelo Eloquent de usuarios. La API lo expone por su ULID; el id interno nunca sale.
// Documentado en docs/base-de-datos/diccionario/users.md

namespace App\Modules\Users\Infrastructure\Models;

use App\Modules\Shared\Domain\EmailAddress;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string $email
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property Carbon $created_at
 */
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = ['name', 'email', 'password', 'is_active'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * El ULID va en su propia columna; la clave primaria sigue siendo el BIGINT.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Correos normalizados desde el origen: sin espacios y en minúsculas.
     *
     * @return Attribute<never, string>
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value): string => EmailAddress::normalize($value));
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }
}
