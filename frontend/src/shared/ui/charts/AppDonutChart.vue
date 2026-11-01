<!-- Dona con el total en el centro y una leyenda con nombre, cantidad y porcentaje (cada fila puede llevar a su detalle).
     El dibujo es decorativo para los lectores de pantalla: la leyenda ya dice todo. Documentado en docs/frontend/sistema-de-diseno/graficos.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatNumber } from '@/shared/format/numbers'
import type { ChartItem } from './types'

const props = defineProps<{ items: ChartItem[]; label: string; totalLabel: string }>()

// Circunferencia de 100 unidades: cada porción mide su porcentaje
const RADIUS = 15.915
const total = computed(() => props.items.reduce((sum, item) => sum + item.value, 0))

const segments = computed(() => {
  let offset = 25 // empieza arriba, como un reloj
  return props.items
    .filter((item) => item.value > 0)
    .map((item) => {
      const size = (item.value / total.value) * 100
      const segment = { key: item.key, tone: item.tone, dash: `${size} ${100 - size}`, offset }
      offset -= size
      return segment
    })
})
</script>

<template>
  <div class="app-donut">
    <div class="app-donut__figure" aria-hidden="true">
      <svg class="app-donut__svg" viewBox="0 0 42 42">
        <circle class="app-donut__track" cx="21" cy="21" :r="RADIUS" />
        <circle
          v-for="segment in segments"
          :key="segment.key"
          class="app-donut__segment"
          :class="`app-donut__segment--${segment.tone}`"
          cx="21"
          cy="21"
          :r="RADIUS"
          :stroke-dasharray="segment.dash"
          :stroke-dashoffset="segment.offset"
        />
      </svg>
      <span class="app-donut__center">
        <strong class="app-donut__total">{{ formatNumber(total) }}</strong>
        <span class="app-donut__total-label">{{ totalLabel }}</span>
      </span>
    </div>

    <ul class="app-donut__legend" :aria-label="label">
      <li v-for="item in items" :key="item.key">
        <component :is="item.to ? 'RouterLink' : 'div'" :to="item.to" class="app-donut__row">
          <span class="app-donut__mark" :class="`app-donut__mark--${item.tone}`" aria-hidden="true" />
          <span class="app-donut__name">{{ item.label }}</span>
          <span class="app-donut__value">{{ formatNumber(item.value) }}</span>
          <span v-if="item.detail" class="app-donut__detail">{{ item.detail }}</span>
        </component>
      </li>
    </ul>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-donut {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: $space-4;
}

.app-donut__figure {
  position: relative;
  flex: none;
  width: $chart-donut-size;
  height: $chart-donut-size;
}

// Con espacio de sobra (una tarjeta pareja con una vecina más alta), la dona crece hasta llenar el alto, con un tope.
// Solo en portátil y monitor: en tablet la leyenda baja bajo la dona y crecer la volvería demasiado alta
@include laptop {
  .app-donut__figure {
    align-self: stretch;
    width: auto;
    min-width: $chart-donut-size;
    max-width: $chart-donut-max;
    height: auto;
    min-height: $chart-donut-size;
    max-height: $chart-donut-max;
    aspect-ratio: 1;
  }
}

.app-donut__svg {
  display: block;
  width: 100%;
  height: 100%;
}

.app-donut__track,
.app-donut__segment {
  fill: none;
  stroke-width: 5;
}

.app-donut__track {
  stroke: $color-page;
}

@each $name, $fill in $chart-fills {
  .app-donut__segment--#{$name} {
    stroke: $fill;
  }

  .app-donut__mark--#{$name} {
    background: $fill;
  }
}

.app-donut__center {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.app-donut__total {
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.app-donut__total-label {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.app-donut__legend {
  flex: 1 1 10rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.app-donut__row {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto 3rem;
  align-items: center;
  gap: $space-2;
  min-height: $touch-target;
  padding: 0 $space-2;
  border-radius: $radius-control;
  color: $color-text;
  font-size: $font-size-sm;
  text-decoration: none;
}

a.app-donut__row:hover,
a.app-donut__row:focus-visible {
  background: $color-page;
}

.app-donut__mark {
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 50%;
}

.app-donut__value {
  font-weight: $font-weight-semibold;
  font-variant-numeric: tabular-nums;
}

.app-donut__detail {
  color: $color-text-secondary;
  font-variant-numeric: tabular-nums;
  text-align: right;
}

@include laptop {
  .app-donut__row {
    min-height: 2rem;
  }
}
</style>
