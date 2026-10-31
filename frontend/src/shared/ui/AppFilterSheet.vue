<!-- Panel de filtros de celular y tablet: sube desde abajo, con los campos en el slot y un pie fijo con "Limpiar" y el
     botón de aplicar. Si los campos no caben, se desplaza el contenido. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QDialog } from 'quasar'
import { useId } from 'vue'
import AppButton from './AppButton.vue'

withDefaults(defineProps<{ title?: string; applyLabel?: string }>(), {
  title: 'Filtros',
  applyLabel: 'Ver resultados',
})
const emit = defineEmits<{ apply: []; clear: [] }>()
const open = defineModel<boolean>('open', { default: false })

const titleId = useId()
</script>

<template>
  <QDialog v-model="open" position="bottom" full-width class="app-popup-sheet">
    <div class="app-filter-sheet" role="dialog" :aria-labelledby="titleId">
      <h2 :id="titleId" class="app-filter-sheet__title">{{ title }}</h2>
      <div class="app-filter-sheet__body">
        <slot />
      </div>
      <div class="app-filter-sheet__footer">
        <AppButton variant="secondary" @click="emit('clear')">Limpiar</AppButton>
        <AppButton @click="emit('apply')">{{ applyLabel }}</AppButton>
      </div>
    </div>
  </QDialog>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-filter-sheet {
  display: flex;
  flex-direction: column;
  width: 100vw;
  max-width: 100vw;
  max-height: 85vh;
  border-radius: $radius-card $radius-card 0 0;
  background: $color-surface;
}

.app-filter-sheet__title {
  margin: 0;
  padding: $space-4 $space-4 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

// Si los filtros no caben, se desplaza el contenido y el pie con los botones queda siempre a la vista
.app-filter-sheet__body {
  display: flex;
  flex-direction: column;
  gap: $space-4;
  padding: $space-4;
  overflow-y: auto;
}

.app-filter-sheet__footer {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: $space-2;
  padding: $space-3 $space-4;
  border-top: 1px solid $color-border;
}
</style>
