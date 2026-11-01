<!-- Ícono propio de Trazo. Toma el color del texto que lo rodea; es decorativo salvo que reciba "label". -->
<!-- Documentado en docs/frontend/sistema-de-diseno/iconos.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { icons, type IconName } from '@/design/icons/icons'

const props = withDefaults(defineProps<{ name: IconName; size?: 'sm' | 'md' | 'lg'; label?: string }>(), {
  size: 'md',
  label: undefined,
})

// Separa la etiqueta SVG de sus atributos para dibujar cada figura
const shapes = computed(() => icons[props.name].map(({ tag, ...attrs }) => ({ tag, attrs })))
</script>

<template>
  <svg
    class="app-icon"
    :class="`app-icon--${size}`"
    viewBox="0 0 24 24"
    :role="label ? 'img' : undefined"
    :aria-label="label"
    :aria-hidden="label ? undefined : 'true'"
    focusable="false"
  >
    <component :is="shape.tag" v-for="(shape, index) in shapes" :key="index" v-bind="shape.attrs" />
  </svg>
</template>

<style scoped lang="scss">
.app-icon {
  flex-shrink: 0;
  fill: none;
  stroke: currentcolor;
  stroke-width: 1.5;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.app-icon--sm {
  width: 1rem;
  height: 1rem;
}

.app-icon--md {
  width: 1.25rem;
  height: 1.25rem;
}

.app-icon--lg {
  width: 1.5rem;
  height: 1.5rem;
}
</style>
