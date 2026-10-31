<!-- Grilla del dashboard. Portátil y monitor: 12 columnas con el lugar y el tamaño que eligió la persona.
     Tablet: 2 columnas en su orden (los anchos ocupan la fila). Celular: indicadores de a dos y el resto a lo ancho.
     Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import type { DashboardWidget, Metrics, TrendDays } from '../domain/dashboard'
import WidgetContent from './widgets/WidgetContent.vue'

defineProps<{ widgets: DashboardWidget[]; metrics: Metrics }>()
const trendDays = defineModel<TrendDays>('trendDays', { required: true })

// Desde este ancho (de 12 columnas) un widget ocupa toda la fila en tablet
const WIDE_FROM = 8

const place = (widget: DashboardWidget) => ({
  '--col-start': widget.x + 1,
  '--row-start': widget.y + 1,
  '--col-span': widget.w,
  '--row-span': widget.h,
})
</script>

<template>
  <div class="dashboard-grid">
    <div
      v-for="widget in widgets"
      :key="widget.code"
      class="dashboard-grid__item"
      :class="{
        'dashboard-grid__item--kpi': widget.kind === 'kpi',
        'dashboard-grid__item--wide': widget.w >= WIDE_FROM,
      }"
      :style="place(widget)"
    >
      <WidgetContent v-model:trend-days="trendDays" :widget="widget" :metrics="metrics" />
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: $space-3;
}

.dashboard-grid__item {
  grid-column: span 2;
  min-width: 0;
}

.dashboard-grid__item--kpi {
  grid-column: span 1;
}

@include tablet {
  .dashboard-grid {
    gap: $space-4;
  }

  .dashboard-grid__item {
    grid-column: span 1;
  }

  .dashboard-grid__item--wide {
    grid-column: span 2;
  }
}

@include laptop {
  .dashboard-grid {
    grid-template-columns: repeat(12, minmax(0, 1fr));
    grid-auto-rows: $dashboard-row-height;
  }

  .dashboard-grid__item,
  .dashboard-grid__item--kpi,
  .dashboard-grid__item--wide {
    grid-row: var(--row-start) / span var(--row-span);
    grid-column: var(--col-start) / span var(--col-span);
  }
}
</style>
