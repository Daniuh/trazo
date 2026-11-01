<?php

declare(strict_types=1);

use App\Modules\Audit\Infrastructure\AuditServiceProvider;
use App\Modules\Auth\Infrastructure\AuthServiceProvider;
use App\Modules\Dashboard\Infrastructure\DashboardServiceProvider;
use App\Modules\Incidents\Infrastructure\IncidentServiceProvider;
use App\Modules\Realtime\Infrastructure\RealtimeServiceProvider;
use App\Modules\System\Infrastructure\SystemServiceProvider;
use App\Modules\Users\Infrastructure\UsersServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    IncidentServiceProvider::class,
    DashboardServiceProvider::class,
    UsersServiceProvider::class,
    AuditServiceProvider::class,
    RealtimeServiceProvider::class,
    SystemServiceProvider::class,
];
