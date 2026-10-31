<!-- Código QR dibujado como SVG a partir de la matriz que calcula la librería qrcode: sin v-html y con los colores de
     los tokens. Deja el margen blanco que exige el estándar para que cualquier cámara lo lea.
     Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import QRCode from 'qrcode'
import { computed } from 'vue'

const props = defineProps<{ value: string; label: string }>()

// Margen de 4 módulos alrededor, como pide la norma del QR
const QUIET_ZONE = 4

const matrix = computed(() => QRCode.create(props.value, { errorCorrectionLevel: 'M' }).modules)
const size = computed(() => matrix.value.size + QUIET_ZONE * 2)

// Un solo trazado con todos los módulos oscuros: liviano aunque el código tenga miles de cuadros
const path = computed(() => {
  const { size: count, data } = matrix.value
  let d = ''
  for (let row = 0; row < count; row++) {
    for (let col = 0; col < count; col++) {
      if (data[row * count + col]) d += `M${col + QUIET_ZONE} ${row + QUIET_ZONE}h1v1h-1z`
    }
  }
  return d
})
</script>

<template>
  <svg class="app-qr-code" :viewBox="`0 0 ${size} ${size}`" role="img" :aria-label="label" shape-rendering="crispEdges">
    <rect class="app-qr-code__background" :width="size" :height="size" />
    <path class="app-qr-code__modules" :d="path" />
  </svg>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-qr-code {
  display: block;
  width: $qr-size;
  height: $qr-size;
  border: 1px solid $color-border;
  border-radius: $radius-control;
}

// Casi blanco con texto casi negro: contraste de sobra para cualquier cámara
.app-qr-code__background {
  fill: $color-surface;
}

.app-qr-code__modules {
  fill: $color-text;
}
</style>
