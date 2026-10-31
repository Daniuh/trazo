<?php

declare(strict_types=1);

use App\Modules\Auth\Infrastructure\AuthServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
];
