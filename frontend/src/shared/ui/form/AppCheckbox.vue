<!-- Casilla de verificación con el estilo de Trazo (envuelve la de Quasar). Alto táctil en celular y tablet.
     `indeterminate` la muestra a medias (p. ej. "Todos" con solo una parte marcada); al tocarla queda marcada.
     Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QCheckbox } from 'quasar'

const props = defineProps<{ label: string; disabled?: boolean; indeterminate?: boolean }>()
const model = defineModel<boolean>({ default: false })

function update(value: boolean | null): void {
  model.value = value ?? true
}
</script>

<template>
  <QCheckbox
    :model-value="props.indeterminate ? null : model"
    class="app-checkbox"
    :label="label"
    :disable="disabled"
    color="primary"
    dense
    @update:model-value="update"
  />
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
