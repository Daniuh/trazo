<!-- Creados vs. resueltos por día: período (7, 30 o 90 días) y totales en una fila, y las columnas debajo,
     ocupando el alto que tenga el widget. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatNumber } from '@/shared/format/numbers'
import AppColumnChart from '@/shared/ui/charts/AppColumnChart.vue'
import type { ChartSeries } from '@/shared/ui/charts/types'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'
import { TREND_DAYS, type TrendData, type TrendDays } from '../../domain/dashboard'

const props = defineProps<{ data: TrendData }>()
const days = defineModel<TrendDays>('days', { required: true })

const SERIES: ChartSeries[] = [
  { key: 'created', label: 'Creados', tone: 'blue' },
  { key: 'resolved', label: 'Resueltos', tone: 'green' },
]
const OPTIONS = TREND_DAYS.map((value) => ({ label: `${value} días`, value }))

// "22 oct": la fecha llega como día (AAAA-MM-DD) y se muestra sin hora
const dayLabel = new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', timeZone: 'UTC' })

const points = computed(() =>
  props.data.series.map((point) => ({
    key: point.date,
    label: dayLabel.format(new Date(`${point.date}T00:00:00Z`)),
    values: { created: point.created, resolved: point.resolved },
  })),
)
</script>

<template>
  <div class="trend">
    <div class="trend__bar">
      <AppSegmented v-model="days" label="Período" :options="OPTIONS" />
      <p class="trend__totals">
        <strong>{{ formatNumber(data.totals.created) }}</strong> creados ·
        <strong>{{ formatNumber(data.totals.resolved) }}</strong> resueltos
      </p>
    </div>
    <AppColumnChart
      class="trend__chart"
      :points="points"
      :series="SERIES"
      :label="`Creados y resueltos por día, últimos ${data.days} días`"
    />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.trend {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  height: 100%;
}

.trend__bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-2;
}

.trend__totals {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;

  strong {
    color: $color-text;
    font-weight: $font-weight-semibold;
  }
}

.trend__chart {
  flex: 1;
  min-height: 0;
}
</style>
