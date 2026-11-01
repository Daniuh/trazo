<!-- Mapa de riesgo: los pendientes por prioridad (filas) y vencimiento (columnas), y al final, aparte, el total de cada
     prioridad en cualquier estado. Cada celda lleva al listado con su filtro; el color de las de vencimiento crece con la
     cantidad frente a la más cargada, y el número siempre está escrito. Es una tabla:
     así se lee bien con lector de pantalla. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { incidentListLocation } from '@/modules/incidents/application/listLink'
import { formatNumber } from '@/shared/format/numbers'
import type { RiskMapData } from '../../domain/dashboard'
import { RISK_COLUMN_LABELS, riskLevel } from '../../domain/risk'

const props = defineProps<{ data: RiskMapData }>()

const max = computed(() => Math.max(0, ...props.data.rows.flatMap((row) => row.cells.map((cell) => cell.count))))
</script>

<template>
  <table class="risk-map">
    <caption class="risk-map__hidden">
      Pendientes por prioridad y vencimiento, y total por prioridad
    </caption>
    <thead>
      <tr>
        <th scope="col"><span class="risk-map__hidden">Prioridad</span></th>
        <th v-for="column in data.columns" :key="column" scope="col" class="risk-map__column">
          {{ RISK_COLUMN_LABELS[column] }}
        </th>
        <th scope="col" class="risk-map__column risk-map__column--total">Total</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="row in data.rows" :key="row.code">
        <th scope="row" class="risk-map__row">{{ row.name }}</th>
        <td v-for="cell in row.cells" :key="cell.bucket">
          <RouterLink
            class="risk-map__cell"
            :class="`risk-map__cell--${riskLevel(cell.count, max)}`"
            :to="incidentListLocation(cell.filter)"
            :aria-label="`${row.name}, ${RISK_COLUMN_LABELS[cell.bucket].toLowerCase()}: ${formatNumber(cell.count)}. Ver en el listado`"
          >
            {{ formatNumber(cell.count) }}
          </RouterLink>
        </td>
        <td class="risk-map__total-cell">
          <RouterLink
            class="risk-map__total"
            :to="incidentListLocation(row.total.filter)"
            :aria-label="`${row.name}, total en cualquier estado: ${formatNumber(row.total.count)}. Ver en el listado`"
          >
            {{ formatNumber(row.total.count) }}
          </RouterLink>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<style scoped lang="scss">
@use 'sass:list';
@use '@/design/tokens' as *;

.risk-map {
  position: relative; // el título oculto queda dentro de la tarjeta
  width: 100%;
  border-spacing: $space-1;
  table-layout: fixed;
  font-size: $font-size-sm;
}

.risk-map__column {
  padding-bottom: $space-1;
  color: $color-text-secondary;
  font-size: $font-size-xs;
  font-weight: $font-weight-medium;
  text-align: center;
}

.risk-map__row {
  width: 30%;
  padding-right: $space-2;
  color: $color-text-secondary;
  font-weight: $font-weight-medium;
  text-align: left;
  white-space: nowrap;
}

.risk-map__cell {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: $risk-cell-height;
  border-radius: $radius-control;
  font-variant-numeric: tabular-nums;
  font-weight: $font-weight-semibold;
  text-decoration: none;

  &:hover {
    outline: 2px solid $color-text;
    outline-offset: -2px;
  }
}

@each $level, $pair in $risk-levels {
  .risk-map__cell--#{$level} {
    background: list.nth($pair, 1);
    color: list.nth($pair, 2);
  }
}

// El total va separado por una línea: no es un grupo de vencimiento, sino todos los de esa prioridad
.risk-map__column--total,
.risk-map__total-cell {
  padding-left: $space-2;
  border-left: 1px solid $color-border;
}

.risk-map__total {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: $risk-cell-height;
  border-radius: $radius-control;
  color: $color-text;
  font-variant-numeric: tabular-nums;
  font-weight: $font-weight-semibold;
  text-decoration: none;

  &:hover {
    background: $color-page;
  }
}

.risk-map__hidden {
  @include visually-hidden;
}
</style>
