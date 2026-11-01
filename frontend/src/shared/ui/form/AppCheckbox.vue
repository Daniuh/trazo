<!-- Casilla de verificación con el estilo de Trazo (envuelve la de Quasar). Alto táctil en celular y tablet.
     Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QCheckbox } from 'quasar'

defineProps<{ label: string; disabled?: boolean }>()
const model = defineModel<boolean>({ default: false })
</script>

<template>
  <QCheckbox v-model="model" class="app-checkbox" :label="label" :disable="disabled" color="primary" dense />
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-checkbox {
  min-height: $touch-target;
  font-size: $font-size-sm;
}

@include laptop {
  .app-checkbox {
    min-height: auto;
  }
}
</style>
