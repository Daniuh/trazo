<!-- Indicador: la cifra grande, que lleva al listado con el filtro que la explica, y una línea de contexto opcional.
     Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { incidentListLocation } from '@/modules/incidents/application/listLink'
import { formatNumber } from '@/shared/format/numbers'
import type { KpiData } from '../../domain/dashboard'

const props = defineProps<{ data: KpiData; name: string; danger?: boolean; caption?: string }>()

const to = computed(() => incidentListLocation(props.data.filter))
</script>

<template>
  <div class="kpi">
    <RouterLink
      :to="to"
      class="kpi__value"
      :class="{ 'kpi__value--danger': danger && data.value > 0 }"
      :aria-label="`${name}: ${formatNumber(data.value)}. Ver en el listado`"
    >
      {{ formatNumber(data.value) }}
    </RouterLink>
    <p v-if="caption" class="kpi__caption">{{ caption }}</p>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.kpi {
  display: flex;
  flex-direction: column;
  gap: $space-1;
}

.kpi__value {
  align-self: flex-start;
  border-radius: $radius-control;
  color: $color-text;
  font-size: $font-size-2xl;
  font-weight: $font-weight-semibold;
  font-variant-numeric: tabular-nums;
  line-height: $line-height-tight;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

// Rojo sobre superficie clara: texto grande con contraste AA
.kpi__value--danger {
  color: $color-danger;
}

.kpi__caption {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}
</style>
