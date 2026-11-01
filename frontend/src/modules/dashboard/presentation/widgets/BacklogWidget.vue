<!-- Balance del backlog: los pendientes de cada día en el periodo (7, 30 o 90 días, el mismo de la tendencia), cuántos
     hay hoy y cuánto subieron o bajaron. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { incidentListLocation } from '@/modules/incidents/application/listLink'
import { formatNumber } from '@/shared/format/numbers'
import AppAreaChart from '@/shared/ui/charts/AppAreaChart.vue'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'
import { TREND_DAYS, type BacklogData, type TrendDays } from '../../domain/dashboard'

const props = defineProps<{ data: BacklogData }>()
const days = defineModel<TrendDays>('days', { required: true })

const OPTIONS = TREND_DAYS.map((value) => ({ label: `${value} días`, value }))
const dayLabel = new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', timeZone: 'UTC' })

const points = computed(() =>
  props.data.series.map((point) => ({
    key: point.date,
    label: dayLabel.format(new Date(`${point.date}T00:00:00Z`)),
    value: point.pending,
  })),
)
const today = computed(() => props.data.series.at(-1)?.pending ?? 0)
const change = computed(() => {
  const value = props.data.change
  if (value === 0) return { tone: 'flat', text: `Igual que hace ${props.data.days} días` }
  const sign = value > 0 ? '+' : '−'
  return {
    tone: value > 0 ? 'up' : 'down',
    text: `${sign}${formatNumber(Math.abs(value))} en ${props.data.days} días`,
  }
})
</script>

<template>
  <div class="backlog">
    <div class="backlog__bar">
      <AppSegmented v-model="days" label="Período" :options="OPTIONS" />
      <p class="backlog__summary">
        Pendientes hoy
        <RouterLink class="backlog__today" :to="incidentListLocation(data.filter)">{{
          formatNumber(today)
        }}</RouterLink>
        <span class="backlog__change" :class="`backlog__change--${change.tone}`">{{ change.text }}</span>
      </p>
    </div>
    <AppAreaChart
      class="backlog__chart"
      :points="points"
      value-label="pendientes"
      :label="`Pendientes de cada día, últimos ${data.days} días`"
    />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.backlog {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  height: 100%;
}

.backlog__bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-2;
}

.backlog__summary {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: $space-2;
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

.backlog__today {
  color: $color-text;
  font-size: $font-size-lg;
  font-variant-numeric: tabular-nums;
  font-weight: $font-weight-semibold;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

// Subir es malo (rojo); bajar, bueno (verde oscuro). Con el texto escrito, el color solo refuerza
.backlog__change {
  font-weight: $font-weight-medium;
}

.backlog__change--up {
  color: $color-danger;
}

.backlog__change--down {
  color: $color-success;
}

.backlog__chart {
  flex: 1;
  min-height: 0;
}
</style>
