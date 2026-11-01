<!-- Anillo de avance con el porcentaje en el centro (p. ej. el cumplimiento del plazo). Sin dato, el anillo queda vacío y
     dice "—". El porcentaje va también en el nombre accesible. Documentado en docs/frontend/sistema-de-diseno/graficos.md -->
<script setup lang="ts">
import { computed } from 'vue'
import type { Tone } from '../tones'

const props = defineProps<{ percent: number | null; label: string; tone?: Tone }>()

// Unidades del dibujo: el SVG se escala al token del tamaño
const RADIUS = 30
const CIRCUMFERENCE = 2 * Math.PI * RADIUS

const value = computed(() => (props.percent === null ? 0 : Math.max(0, Math.min(100, props.percent))))
const dash = computed(() => `${(CIRCUMFERENCE * value.value) / 100} ${CIRCUMFERENCE}`)
const name = computed(() =>
  props.percent === null ? `${props.label}: sin datos` : `${props.label}: ${props.percent} %`,
)
</script>

<template>
  <svg class="app-ring" viewBox="0 0 76 76" role="img" :aria-label="name">
    <circle class="app-ring__track" cx="38" cy="38" :r="RADIUS" />
    <circle
      v-if="percent !== null && value > 0"
      class="app-ring__value"
      :class="`app-ring__value--${tone ?? 'green'}`"
      cx="38"
      cy="38"
      :r="RADIUS"
      :stroke-dasharray="dash"
      transform="rotate(-90 38 38)"
    />
    <text class="app-ring__text" x="38" y="38" text-anchor="middle" dominant-baseline="central">
      {{ percent === null ? '—' : `${percent} %` }}
    </text>
  </svg>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-ring {
  flex: none;
  width: $chart-ring-size;
  height: $chart-ring-size;
}

.app-ring__track,
.app-ring__value {
  fill: none;
  stroke-width: 8;
}

.app-ring__track {
  stroke: $color-border;
}

.app-ring__value {
  stroke-linecap: round;
}

@each $name, $fill in $chart-fills {
  .app-ring__value--#{$name} {
    stroke: $fill;
  }
}

.app-ring__text {
  fill: $color-text;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
}
</style>
