<!-- Pulso de la operación: una frase con lo esencial (vencidos, qué parte de los pendientes son y si hoy el backlog sube),
     el anillo del cumplimiento del plazo y tres cifras que llevan al listado. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { incidentListLocation } from '@/modules/incidents/application/listLink'
import { formatNumber } from '@/shared/format/numbers'
import AppRing from '@/shared/ui/charts/AppRing.vue'
import type { PulseData } from '../../domain/dashboard'
import { backlogTrend } from '../../domain/risk'

const props = defineProps<{ data: PulseData }>()

const TREND_TEXT = { up: 'el backlog sube', down: 'el backlog baja', flat: 'el backlog se mantiene' } as const

const trend = computed(() => TREND_TEXT[backlogTrend(props.data.today.created, props.data.today.resolved)])
const overdueText = computed(() => {
  const value = props.data.overdue.value
  return value === 1 ? '1 vencido' : `${formatNumber(value)} vencidos`
})
const figures = computed(() => [
  { key: 'total', label: 'Total', value: props.data.total.value, filter: props.data.total.filter },
  { key: 'pending', label: 'Pendientes', value: props.data.pending.value, filter: props.data.pending.filter },
  ...(props.data.critical
    ? [
        {
          key: 'critical',
          label: `Prioridad ${props.data.critical.name}`,
          value: props.data.critical.value,
          filter: props.data.critical.filter,
        },
      ]
    : []),
])
const onTimeCaption = computed(() =>
  props.data.onTime.resolved === 0
    ? `Nada resuelto en ${props.data.onTime.days} días`
    : `De ${formatNumber(props.data.onTime.resolved)} resueltos en ${props.data.onTime.days} días`,
)
</script>

<template>
  <div class="pulse">
    <p class="pulse__summary">
      <RouterLink class="pulse__overdue" :to="incidentListLocation(data.overdue.filter)">{{ overdueText }}</RouterLink
      >, el {{ data.overdue.shareOfPending }} % de los pendientes. Hoy se crearon
      <strong>{{ formatNumber(data.today.created) }}</strong> y se resolvieron
      <strong>{{ formatNumber(data.today.resolved) }}</strong
      >: {{ trend }}.
    </p>

    <div class="pulse__on-time">
      <AppRing :percent="data.onTime.percent" label="Cumplimiento del plazo" />
      <div>
        <p class="pulse__on-time-title">Cumplimiento del plazo</p>
        <p class="pulse__caption">{{ onTimeCaption }}</p>
      </div>
    </div>

    <dl class="pulse__figures">
      <div v-for="figure in figures" :key="figure.key" class="pulse__figure">
        <dt class="pulse__caption">{{ figure.label }}</dt>
        <dd>
          <RouterLink
            class="pulse__value"
            :to="incidentListLocation(figure.filter)"
            :aria-label="`${figure.label}: ${formatNumber(figure.value)}. Ver en el listado`"
          >
            {{ formatNumber(figure.value) }}
          </RouterLink>
        </dd>
      </div>
    </dl>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Celular y tablet: uno debajo del otro
.pulse {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

.pulse__summary {
  margin: 0;
  font-size: $font-size-md;
  line-height: $line-height-base;

  strong {
    font-weight: $font-weight-semibold;
  }
}

// Rojo sobre superficie clara con peso: contraste AA
.pulse__overdue {
  color: $color-danger;
  font-weight: $font-weight-semibold;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

.pulse__on-time {
  display: flex;
  align-items: center;
  gap: $space-3;

  p {
    margin: 0;
  }
}

.pulse__on-time-title {
  font-weight: $font-weight-semibold;
}

.pulse__caption {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.pulse__figures {
  display: flex;
  gap: $space-5;
  margin: 0;

  dd {
    margin: 0;
  }
}

.pulse__value {
  color: $color-text;
  font-size: $font-size-xl;
  font-variant-numeric: tabular-nums;
  font-weight: $font-weight-semibold;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

// Portátil y monitor: frase, anillo y cifras en una fila
@include laptop {
  .pulse {
    flex-direction: row;
    align-items: center;
    gap: $space-6;
  }

  .pulse__summary {
    flex: 1;
    font-size: $font-size-lg;
  }
}
</style>
