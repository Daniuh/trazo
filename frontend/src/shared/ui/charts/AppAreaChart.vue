<!-- Línea con área debajo para una sola serie en el tiempo (p. ej. los pendientes de cada día). La escala va del mínimo
     al máximo del periodo, para que la tendencia se note; abajo, las fechas de inicio, mitad y fin. Al pasar el cursor,
     cada punto dice su valor. Para lectores de pantalla hay una tabla oculta con todos los datos.
     Documentado en docs/frontend/sistema-de-diseno/graficos.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatNumber } from '@/shared/format/numbers'

export interface AreaPoint {
  key: string
  label: string
  value: number
}

const props = defineProps<{ points: AreaPoint[]; label: string; valueLabel: string }>()

const WIDTH = 100
const HEIGHT = 100
// Margen arriba y abajo del rango, para que la línea no toque los bordes
const PADDING = 0.1

const range = computed(() => {
  const values = props.points.map((point) => point.value)
  const min = Math.min(...values)
  const max = Math.max(...values)
  const span = Math.max(1, max - min)
  return { low: min - span * PADDING, high: max + span * PADDING }
})

const coords = computed(() => {
  const last = Math.max(1, props.points.length - 1)
  const { low, high } = range.value
  return props.points.map((point, index) => ({
    ...point,
    x: (index / last) * WIDTH,
    y: HEIGHT - ((point.value - low) / (high - low)) * HEIGHT,
  }))
})

const line = computed(() => coords.value.map((point) => `${point.x},${point.y}`).join(' '))
const area = computed(() => `0,${HEIGHT} ${line.value} ${WIDTH},${HEIGHT}`)
const slot = computed(() => WIDTH / Math.max(1, props.points.length))

const ticks = computed(() => {
  const last = props.points.length - 1
  const picks = new Set([0, Math.floor(last / 2), last])
  return [...picks].filter((index) => index >= 0).map((index) => props.points[index]?.label ?? '')
})
</script>

<template>
  <figure class="app-area">
    <div class="app-area__plot" aria-hidden="true">
      <div class="app-area__canvas">
        <svg class="app-area__svg" :viewBox="`0 0 ${WIDTH} ${HEIGHT}`" preserveAspectRatio="none">
          <polygon class="app-area__fill" :points="area" />
          <polyline class="app-area__line" :points="line" />
          <!-- Zona invisible por punto, para mostrar su valor al pasar el cursor -->
          <rect
            v-for="point in coords"
            :key="point.key"
            class="app-area__hit"
            :x="point.x - slot / 2"
            y="0"
            :width="slot"
            :height="HEIGHT"
          >
            <title>{{ point.label }}: {{ formatNumber(point.value) }} {{ valueLabel }}</title>
          </rect>
        </svg>
      </div>
      <div class="app-area__ticks">
        <span v-for="tick in ticks" :key="tick">{{ tick }}</span>
      </div>
    </div>

    <!-- Una tabla no se deja encoger a 1 px: el contenedor es el que se oculta -->
    <div class="app-area__data">
      <table>
        <caption>
          {{
            label
          }}
        </caption>
        <thead>
          <tr>
            <th scope="col">Fecha</th>
            <th scope="col">{{ valueLabel }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="point in points" :key="point.key">
            <th scope="row">{{ point.label }}</th>
            <td>{{ point.value }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </figure>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Ocupa el alto que le dé su contenedor; sin alto definido (celular y tablet), el mínimo del token
.app-area {
  position: relative; // la tabla oculta queda anclada al gráfico y no a la página
  display: flex;
  flex-direction: column;
  height: 100%;
  margin: 0;
}

.app-area__plot {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: $chart-columns-height;
}

.app-area__canvas {
  position: relative;
  flex: 1;
  min-height: 0;
}

.app-area__svg {
  position: absolute;
  inset: 0;
  display: block;
  width: 100%;
  height: 100%;
}

.app-area__fill {
  fill: $chart-area-fill;
}

.app-area__line {
  fill: none;
  stroke: $color-blue-base;
  stroke-width: 2;
  stroke-linejoin: round;
  vector-effect: non-scaling-stroke;
}

.app-area__hit {
  fill: transparent;

  &:hover {
    fill: $color-border;
    fill-opacity: 0.4;
  }
}

.app-area__ticks {
  display: flex;
  justify-content: space-between;
  margin-top: $space-1;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.app-area__data {
  @include visually-hidden;
}
</style>
