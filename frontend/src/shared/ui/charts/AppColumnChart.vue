<!-- Columnas agrupadas por punto (p. ej. creados y resueltos por día), con leyenda y fechas de inicio, mitad y fin.
     Al pasar el cursor, cada grupo muestra sus valores. Para lectores de pantalla hay una tabla oculta con todos los datos.
     Documentado en docs/frontend/sistema-de-diseno/graficos.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatNumber } from '@/shared/format/numbers'
import type { ChartPoint, ChartSeries } from './types'

const props = defineProps<{ points: ChartPoint[]; series: ChartSeries[]; label: string }>()

// Unidades del dibujo: cada punto ocupa un ancho fijo y el SVG se estira al contenedor
const SLOT = 10
const HEIGHT = 100

const max = computed(() =>
  Math.max(1, ...props.points.flatMap((point) => props.series.map((s) => point.values[s.key] ?? 0))),
)
const barWidth = computed(() => (SLOT * 0.8) / props.series.length)

const bars = computed(() =>
  props.points.flatMap((point, index) =>
    props.series.map((serie, position) => {
      const value = point.values[serie.key] ?? 0
      const height = (value / max.value) * HEIGHT
      return {
        key: `${point.key}-${serie.key}`,
        tone: serie.tone,
        x: index * SLOT + SLOT * 0.1 + position * barWidth.value,
        y: HEIGHT - height,
        height,
      }
    }),
  ),
)

const groups = computed(() =>
  props.points.map((point, index) => ({
    key: point.key,
    x: index * SLOT,
    title: `${point.label}: ${props.series.map((s) => `${formatNumber(point.values[s.key] ?? 0)} ${s.label.toLowerCase()}`).join(', ')}`,
  })),
)

// Solo inicio, mitad y fin: con 90 días, más fechas no caben
const ticks = computed(() => {
  const last = props.points.length - 1
  const picks = new Set([0, Math.floor(last / 2), last])
  return [...picks].filter((index) => index >= 0).map((index) => props.points[index]?.label ?? '')
})
</script>

<template>
  <figure class="app-columns">
    <ul class="app-columns__legend" aria-hidden="true">
      <li v-for="serie in series" :key="serie.key" class="app-columns__legend-item">
        <span class="app-columns__mark" :class="`app-columns__mark--${serie.tone}`" />{{ serie.label }}
      </li>
      <li class="app-columns__max">Máximo por día: {{ formatNumber(max) }}</li>
    </ul>

    <div class="app-columns__plot" aria-hidden="true">
      <svg class="app-columns__svg" :viewBox="`0 0 ${points.length * SLOT} ${HEIGHT}`" preserveAspectRatio="none">
        <line class="app-columns__base" x1="0" :x2="points.length * SLOT" :y1="HEIGHT" :y2="HEIGHT" />
        <rect
          v-for="bar in bars"
          :key="bar.key"
          class="app-columns__bar"
          :class="`app-columns__bar--${bar.tone}`"
          :x="bar.x"
          :y="bar.y"
          :width="barWidth"
          :height="bar.height"
        />
        <!-- Zona invisible por grupo, para mostrar sus valores al pasar el cursor -->
        <rect
          v-for="group in groups"
          :key="group.key"
          class="app-columns__hit"
          :x="group.x"
          y="0"
          :width="SLOT"
          :height="HEIGHT"
        >
          <title>{{ group.title }}</title>
        </rect>
      </svg>
      <div class="app-columns__ticks">
        <span v-for="tick in ticks" :key="tick">{{ tick }}</span>
      </div>
    </div>

    <table class="app-columns__table">
      <caption>
        {{
          label
        }}
      </caption>
      <thead>
        <tr>
          <th scope="col">Fecha</th>
          <th v-for="serie in series" :key="serie.key" scope="col">{{ serie.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="point in points" :key="point.key">
          <th scope="row">{{ point.label }}</th>
          <td v-for="serie in series" :key="serie.key">{{ point.values[serie.key] ?? 0 }}</td>
        </tr>
      </tbody>
    </table>
  </figure>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Ocupa el alto que le dé su contenedor; sin alto definido (celular y tablet), el mínimo del token
.app-columns {
  position: relative; // la tabla oculta queda anclada al gráfico y no a la página
  display: flex;
  flex-direction: column;
  gap: $space-2;
  height: 100%;
  margin: 0;
}

.app-columns__legend {
  display: flex;
  flex-wrap: wrap;
  gap: $space-4;
  margin: 0;
  padding: 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
  list-style: none;
}

.app-columns__legend-item {
  display: inline-flex;
  align-items: center;
  gap: $space-2;
}

.app-columns__mark {
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 2px;
}

@each $name, $fill in $chart-fills {
  .app-columns__mark--#{$name} {
    background: $fill;
  }

  .app-columns__bar--#{$name} {
    fill: $fill;
  }
}

.app-columns__max {
  margin-left: auto;
}

.app-columns__plot {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: $chart-columns-height;
}

.app-columns__svg {
  display: block;
  flex: 1;
  width: 100%;
  min-height: 0;
}

.app-columns__base {
  stroke: $color-border-strong;
  stroke-width: 1;
  vector-effect: non-scaling-stroke;
}

.app-columns__hit {
  fill: transparent;

  &:hover {
    fill: $color-border;
    fill-opacity: 0.4;
  }
}

.app-columns__ticks {
  display: flex;
  justify-content: space-between;
  margin-top: $space-1;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.app-columns__table {
  @include visually-hidden;
}
</style>
