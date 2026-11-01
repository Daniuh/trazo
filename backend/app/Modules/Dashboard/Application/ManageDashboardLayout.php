<?php

declare(strict_types=1);

// Casos de uso del diseño del dashboard: verlo, guardarlo y volver al original.
// Solo se ofrecen los widgets que la persona puede usar; uno nuevo en el catálogo aparece oculto, listo para agregarse.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application;

use App\Modules\Dashboard\Application\Data\DashboardView;
use App\Modules\Dashboard\Application\Data\WidgetView;
use App\Modules\Dashboard\Application\Ports\LayoutStore;
use App\Modules\Dashboard\Application\Ports\WidgetCatalog;
use App\Modules\Dashboard\Domain\DashboardAccess;
use App\Modules\Dashboard\Domain\DashboardGrid;
use App\Modules\Dashboard\Domain\Placement;
use App\Modules\Dashboard\Domain\WidgetDefinition;
use App\Modules\Dashboard\Domain\WidgetLayout;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;

final readonly class ManageDashboardLayout
{
    public function __construct(
        private WidgetCatalog $catalog,
        private LayoutStore $store,
    ) {}

    public function show(Actor $actor): DashboardView
    {
        return $this->view($actor, $this->allowed($actor), $this->store->saved($actor->id));
    }

    /**
     * @param  list<WidgetLayout>  $layouts
     */
    public function save(Actor $actor, array $layouts): DashboardView
    {
        $allowed = $this->allowed($actor);
        DashboardGrid::validate($layouts, $allowed);

        $byCode = [];
        foreach ($layouts as $layout) {
            $byCode[$layout->code->value] = $layout;
        }

        $this->store->replace($actor->id, $this->complete($allowed, $byCode));

        return $this->view($actor, $allowed, $byCode);
    }

    public function reset(Actor $actor): DashboardView
    {
        $allowed = $this->allowed($actor);
        $this->store->clear($actor->id);

        return $this->view($actor, $allowed, null);
    }

    /**
     * @return array<string, WidgetDefinition> En el orden del catálogo
     */
    private function allowed(Actor $actor): array
    {
        if (! DashboardAccess::canView($actor)) {
            throw new ForbiddenException;
        }

        $allowed = [];
        foreach ($this->catalog->active() as $definition) {
            if ($definition->allows($actor)) {
                $allowed[$definition->code->value] = $definition;
            }
        }

        return $allowed;
    }

    /**
     * @param  array<string, WidgetDefinition>  $allowed
     * @param  array<string, WidgetLayout>|null  $saved
     */
    private function view(Actor $actor, array $allowed, ?array $saved): DashboardView
    {
        $layouts = $saved === null ? $this->defaults($allowed) : $this->complete($allowed, $saved);

        return new DashboardView(
            DashboardAccess::isGeneral($actor),
            $saved !== null,
            array_map(fn (WidgetLayout $layout): WidgetView => new WidgetView($allowed[$layout->code->value], $layout), $layouts),
        );
    }

    /**
     * @param  array<string, WidgetDefinition>  $allowed
     * @return list<WidgetLayout>
     */
    private function defaults(array $allowed): array
    {
        $original = array_filter($allowed, fn (WidgetDefinition $definition): bool => $definition->inDefaultLayout);
        $placements = DashboardGrid::defaults(array_values($original));
        $saved = [];
        foreach ($placements as $code => $placement) {
            $saved[$code] = new WidgetLayout($allowed[$code]->code, $placement, true);
        }

        // Los que no son del diseño original quedan ocultos, debajo de todo
        return $this->complete($allowed, $saved);
    }

    /**
     * Un diseño guardado con todos los widgets permitidos: los que faltan quedan ocultos, debajo de todo.
     * Si el catálogo cambió sus topes, el tamaño guardado se ajusta para seguir siendo válido.
     *
     * @param  array<string, WidgetDefinition>  $allowed
     * @param  array<string, WidgetLayout>  $saved
     * @return list<WidgetLayout>
     */
    private function complete(array $allowed, array $saved): array
    {
        $bottom = 0;
        foreach ($saved as $code => $layout) {
            if (isset($allowed[$code])) {
                $bottom = max($bottom, $layout->placement->bottom());
            }
        }
        $bottom = min($bottom, DashboardGrid::MAX_ROW);

        $result = [];
        foreach ($allowed as $code => $definition) {
            $layout = $saved[$code] ?? null;
            $result[] = $layout === null
                ? new WidgetLayout($definition->code, new Placement(0, $bottom, $definition->defaultW, $definition->defaultH), false)
                : new WidgetLayout($definition->code, DashboardGrid::fit($definition, $layout->placement), $layout->visible);
        }

        return $result;
    }
}
