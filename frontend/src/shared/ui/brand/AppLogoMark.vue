<!-- Aro de Trazo: tres trazos (azul, verde y naranja) que representan el ciclo de vida de un incidente. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/marca.md -->
<script setup lang="ts">
// "hairline": trazo fino, para usar el aro como decoración grande (fondo de las pantallas sin sesión)
withDefaults(defineProps<{ spinning?: boolean; weight?: 'bold' | 'hairline' }>(), { spinning: false, weight: 'bold' })

// Cada trazo ocupa un tercio del aro menos el espacio que los separa
const segments = [
  { tone: 'blue', rotate: -80 },
  { tone: 'green', rotate: 40 },
  { tone: 'orange', rotate: 160 },
] as const
</script>

<template>
  <svg
    class="logo-mark"
    :class="[`logo-mark--${weight}`, { 'logo-mark--spinning': spinning }]"
    viewBox="0 0 32 32"
    aria-hidden="true"
  >
    <g class="logo-mark__ring">
      <circle
        v-for="segment in segments"
        :key="segment.tone"
        :class="`logo-mark__segment logo-mark__segment--${segment.tone}`"
        cx="16"
        cy="16"
        r="12"
        :transform="`rotate(${segment.rotate} 16 16)`"
      />
    </g>
  </svg>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.logo-mark {
  display: block;
  width: 1em;
  height: 1em;
}

.logo-mark__segment {
  fill: none;
  stroke-width: 5;
  stroke-linecap: round;
  stroke-dasharray: 19.1 56.3;
}

// Trazo fino: sin las puntas redondas del grueso, cada tramo se alarga para conservar la misma separación
.logo-mark--hairline .logo-mark__segment {
  stroke-width: 0.3;
  stroke-dasharray: 23.8 51.6;
}

.logo-mark__segment--blue {
  stroke: $color-blue-base;
}

.logo-mark__segment--green {
  stroke: $color-green-base;
}

.logo-mark__segment--orange {
  stroke: $color-orange-base;
}

.logo-mark--spinning .logo-mark__ring {
  transform-origin: 16px 16px;
  animation: logo-spin 1.4s linear infinite;
}

@keyframes logo-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
