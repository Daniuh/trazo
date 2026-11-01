<!-- Panel de filtros de celular y tablet: sube desde abajo, con los campos en el slot y un pie fijo con "Limpiar" y el
     botón de aplicar. Si los campos no caben, se desplaza el contenido. Se apoya en AppSheet.
     Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import AppButton from './AppButton.vue'
import AppSheet from './AppSheet.vue'

withDefaults(defineProps<{ title?: string; applyLabel?: string }>(), {
  title: 'Filtros',
  applyLabel: 'Ver resultados',
})
const emit = defineEmits<{ apply: []; clear: [] }>()
const open = defineModel<boolean>('open', { default: false })
</script>

<template>
  <AppSheet v-model:open="open" :title="title" side="bottom" :closable="false">
    <slot />
    <template #footer>
      <div class="app-filter-sheet__footer">
        <AppButton variant="secondary" @click="emit('clear')">Limpiar</AppButton>
        <AppButton @click="emit('apply')">{{ applyLabel }}</AppButton>
      </div>
    </template>
  </AppSheet>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-filter-sheet__footer {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: $space-2;
}
</style>
