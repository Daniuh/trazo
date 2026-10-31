<!-- Dibuja un widget según su código: pulso, mapa de riesgo, flujo, balance, indicador, barras, dona, tendencia o lista. Los colores salen de los tonos
     de estados y prioridades; cada fila lleva al listado con su filtro. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { incidentListLocation } from '@/modules/incidents/application/listLink'
import { priorityTone, statusTone } from '@/modules/incidents/presentation/incidentTones'
import { formatNumber } from '@/shared/format/numbers'
import AppBarList from '@/shared/ui/charts/AppBarList.vue'
import AppDonutChart from '@/shared/ui/charts/AppDonutChart.vue'
import type { ChartItem } from '@/shared/ui/charts/types'
import { type BreakdownData, type DashboardWidget, type Metrics, type TrendDays } from '../../domain/dashboard'
import BacklogWidget from './BacklogWidget.vue'
import DigestWidget from './DigestWidget.vue'
import KpiWidget from './KpiWidget.vue'
import PulseWidget from './PulseWidget.vue'
import RiskMapWidget from './RiskMapWidget.vue'
import StatusFlowWidget from './StatusFlowWidget.vue'
import TrendWidget from './TrendWidget.vue'
import WidgetCard from './WidgetCard.vue'

const props = defineProps<{ widget: DashboardWidget; metrics: Metrics }>()
const trendDays = defineModel<TrendDays>('trendDays', { required: true })

const m = computed(() => props.metrics)
const ready = computed(() => props.metrics[props.widget.code] !== undefined)

const breakdown = (data: BreakdownData | undefined, tone: typeof statusTone, percent = false): ChartItem[] =>
  (data?.items ?? []).map((item) => ({
    key: item.code,
    label: item.name,
    value: item.count,
    tone: tone(item.code),
    detail: percent && item.percent !== undefined ? `${item.percent} %` : undefined,
    to: incidentListLocation(item.filter),
  }))

const statusItems = computed(() => breakdown(m.value.incidents_by_status, statusTone))
const priorityItems = computed(() => breakdown(m.value.incidents_by_priority, priorityTone, true))
const workloadItems = computed<ChartItem[]>(() =>
  (m.value.workload_by_agent?.items ?? []).map((row) => ({
    key: row.id,
    label: row.name,
    value: row.pending,
    tone: 'blue',
    detail: row.overdue > 0 ? `${formatNumber(row.overdue)} ${row.overdue === 1 ? 'vencido' : 'vencidos'}` : undefined,
    to: incidentListLocation(row.filter),
  })),
)

const overdueCaption = computed(() => {
  const share = m.value.overdue_incidents?.shareOfPending
  return share === undefined ? undefined : `${share} % de los pendientes`
})
</script>

<template>
  <WidgetCard :title="widget.name" :description="widget.description" :ready="ready">
    <PulseWidget v-if="widget.code === 'operation_pulse' && m.operation_pulse" :data="m.operation_pulse" />
    <RiskMapWidget v-else-if="widget.code === 'risk_map' && m.risk_map" :data="m.risk_map" />
    <StatusFlowWidget v-else-if="widget.code === 'status_flow' && m.status_flow" :data="m.status_flow" />
    <BacklogWidget
      v-else-if="widget.code === 'backlog_balance' && m.backlog_balance"
      v-model:days="trendDays"
      :data="m.backlog_balance"
    />
    <KpiWidget
      v-else-if="widget.code === 'total_incidents' && m.total_incidents"
      :data="m.total_incidents"
      :name="widget.name"
    />
    <KpiWidget
      v-else-if="widget.code === 'pending_incidents' && m.pending_incidents"
      :data="m.pending_incidents"
      :name="widget.name"
    />
    <KpiWidget
      v-else-if="widget.code === 'overdue_incidents' && m.overdue_incidents"
      :data="m.overdue_incidents"
      :name="widget.name"
      :caption="overdueCaption"
      danger
    />
    <KpiWidget
      v-else-if="widget.code === 'unassigned_incidents' && m.unassigned_incidents"
      :data="m.unassigned_incidents"
      :name="widget.name"
      caption="Pendientes sin responsable"
    />
    <AppBarList v-else-if="widget.code === 'incidents_by_status'" :items="statusItems" label="Incidentes por estado" />
    <AppDonutChart
      v-else-if="widget.code === 'incidents_by_priority'"
      :items="priorityItems"
      label="Incidentes por prioridad"
      total-label="incidentes"
    />
    <TrendWidget
      v-else-if="widget.code === 'created_vs_resolved' && m.created_vs_resolved"
      v-model:days="trendDays"
      :data="m.created_vs_resolved"
    />
    <DigestWidget
      v-else-if="widget.code === 'due_soon' && m.due_soon"
      :data="m.due_soon"
      empty="Ningún pendiente vence en las próximas 48 horas."
    />
    <DigestWidget
      v-else-if="widget.code === 'assigned_to_me' && m.assigned_to_me"
      :data="m.assigned_to_me"
      empty="No tienes pendientes asignados."
    />
    <template v-else-if="widget.code === 'workload_by_agent'">
      <AppBarList v-if="workloadItems.length > 0" :items="workloadItems" label="Pendientes por persona" />
      <p v-else class="widget-content__empty">Nadie tiene pendientes asignados.</p>
    </template>
  </WidgetCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.widget-content__empty {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}
</style>
