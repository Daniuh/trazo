<!-- Panel que se abre sobre la página: desde la derecha en portátil y monitor, desde abajo en celular y tablet (o
     siempre desde abajo con side="bottom"). Título, botón de cerrar, contenido que se desplaza si no cabe y un pie
     opcional que queda a la vista. Base de AppFilterSheet. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QDialog } from 'quasar'
import { computed, useId } from 'vue'
import AppIcon from './AppIcon.vue'
import { useViewport } from './useViewport'

const props = withDefaults(defineProps<{ title: string; side?: 'auto' | 'bottom'; closable?: boolean }>(), {
  side: 'auto',
  closable: true,
})
const open = defineModel<boolean>('open', { default: false })

const titleId = useId()
const { isLaptopUp } = useViewport()
const position = computed(() => (props.side === 'auto' && isLaptopUp.value ? 'right' : 'bottom'))
</script>

<template>
  <QDialog
    v-model="open"
    :position="position"
    :full-width="position === 'bottom'"
    :full-height="position === 'right'"
    class="app-popup-sheet"
  >
    <div class="app-sheet" :class="`app-sheet--${position}`" role="dialog" :aria-labelledby="titleId">
      <header class="app-sheet__header">
        <h2 :id="titleId" class="app-sheet__title">{{ title }}</h2>
        <button v-if="closable" class="app-sheet__close" type="button" aria-label="Cerrar" @click="open = false">
          <AppIcon name="close" />
        </button>
      </header>
      <div class="app-sheet__body">
        <slot />
      </div>
      <div v-if="$slots.footer" class="app-sheet__footer">
        <slot name="footer" />
      </div>
    </div>
  </QDialog>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-sheet {
  display: flex;
  flex-direction: column;
  background: $color-surface;
}

.app-sheet--bottom {
  width: 100vw;
  max-width: 100vw;
  max-height: 85vh;
  border-radius: $radius-card $radius-card 0 0;
}

.app-sheet--right {
  width: $preview-width;
  max-width: 100vw;
  height: 100vh;
  max-height: 100vh;
  border-radius: $radius-card 0 0 $radius-card;
}

.app-sheet__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: $space-3;
  padding: $space-4 $space-4 0;
}

.app-sheet__title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

.app-sheet__close {
  display: inline-flex;
  padding: $space-1;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text-secondary;
  cursor: pointer;

  &:hover {
    color: $color-text;
  }
}

// Si el contenido no cabe, se desplaza y el pie queda siempre a la vista
.app-sheet__body {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: $space-4;
  padding: $space-4;
  overflow-y: auto;
}

.app-sheet__footer {
  padding: $space-3 $space-4;
  border-top: 1px solid $color-border;
}
</style>
