<!-- Barras horizontales: nombre, barra proporcional y valor en texto. Cada fila puede llevar a su detalle.
     Es una lista real, así que los lectores de pantalla leen "Abierto, 141". Documentado en docs/frontend/sistema-de-diseno/graficos.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatNumber } from '@/shared/format/numbers'
import type { ChartItem } from './types'

const props = defineProps<{ items: ChartItem[]; label: string }>()

const max = computed(() => Math.max(1, ...props.items.map((item) => item.value)))
const width = (value: number) => `${Math.round((value / max.value) * 100)}%`
</script>

<template>
  <ul class="app-bars" :aria-label="label">
    <li v-for="item in items" :key="item.key">
      <component :is="item.to ? 'RouterLink' : 'div'" :to="item.to" class="app-bars__row">
        <span class="app-bars__label">{{ item.label }}</span>
        <span class="app-bars__track" aria-hidden="true">
          <span class="app-bars__fill" :class="`app-bars__fill--${item.tone}`" :style="{ width: width(item.value) }" />
        </span>
        <span class="app-bars__value">
          {{ formatNumber(item.value) }}<span v-if="item.detail" class="app-bars__detail"> · {{ item.detail }}</span>
        </span>
      </component>
    </li>
  </ul>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Si el contenedor es más alto que las barras (una tarjeta pareja con su vecina), las filas se reparten el alto
.app-bars {
  display: flex;
  flex-direction: column;
  justify-content: space-around;
  gap: $space-1;
  margin: 0;
  padding: 0;
  list-style: none;
}

.app-bars__row {
  display: grid;
  grid-template-columns: $chart-label-width minmax(0, 1fr) auto;
  align-items: center;
  gap: $space-3;
  min-height: $touch-target;
  padding: 0 $space-2;
  border-radius: $radius-control;
  color: $color-text;
  font-size: $font-size-sm;
  text-decoration: none;
}

a.app-bars__row:hover,
a.app-bars__row:focus-visible {
  background: $color-page;
}

.app-bars__label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.app-bars__track {
  height: $chart-bar-height;
  border-radius: $radius-pill;
  background: $color-page;
}

.app-bars__fill {
  display: block;
  height: 100%;
  min-width: 2px;
  border-radius: $radius-pill;
}

@each $name, $fill in $chart-fills {
  .app-bars__fill--#{$name} {
    background: $fill;
  }
}

.app-bars__value {
  font-weight: $font-weight-semibold;
  font-variant-numeric: tabular-nums;
  text-align: right;
  white-space: nowrap;
}

.app-bars__detail {
  color: $color-text-secondary;
  font-weight: $font-weight-regular;
}

@include laptop {
  .app-bars__row {
    min-height: 2rem;
  }
}
</style>
