<!-- Campo que se ve como un selector y abre un panel: menú bajo el campo en portátil y monitor,
     panel inferior de borde a borde en celular y tablet. Lo usan los campos de fecha. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QDialog, QField, QMenu } from 'quasar'
import { useId } from 'vue'
import type { IconName } from '@/design/icons/icons'
import AppIcon from '../AppIcon.vue'
import { useViewport } from '../useViewport'
import AppField from './AppField.vue'

defineProps<{
  label: string
  text: string
  empty: boolean
  icon: IconName
  info?: string
  error?: string
  required?: boolean
}>()

const open = defineModel<boolean>('open', { default: false })
const id = useId()
const { isLaptopUp } = useViewport()

// En portátil y monitor el menú se abre solo (clic o Enter sobre el campo); en pantallas chicas lo abre el contenedor
function openSheet(): void {
  if (!isLaptopUp.value) open.value = true
}
</script>

<template>
  <AppField :id="id" :label="label" :info="info" :error="error" :required="required">
    <div class="app-popup-field" @click="openSheet">
      <QField
        class="app-popup-field__control"
        :class="{ 'app-popup-field__control--empty': empty }"
        :model-value="empty ? null : text"
        outlined
        dense
        hide-bottom-space
      >
        <template #control>
          <div
            :id="id"
            class="app-popup-field__value"
            role="button"
            tabindex="0"
            aria-haspopup="dialog"
            :aria-expanded="open"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${id}-error` : undefined"
            :aria-label="`${label}: ${text}`"
            @keydown.space.prevent="open = true"
          >
            {{ text }}
          </div>
        </template>
        <template #append><AppIcon :name="icon" size="sm" /></template>
      </QField>

      <!-- El menú se ancla a este contenedor: QField no muestra lo que se pone dentro de él -->
      <QMenu v-if="isLaptopUp" v-model="open" anchor="bottom left" self="top left" :offset="[0, 6]">
        <slot />
      </QMenu>
    </div>

    <QDialog v-if="!isLaptopUp" v-model="open" position="bottom" full-width class="app-popup-sheet">
      <div class="app-popup-field__sheet"><slot /></div>
    </QDialog>
  </AppField>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-popup-field__control {
  cursor: pointer;
}

.app-popup-field__value {
  overflow: hidden;
  align-self: center;
  width: 100%;
  outline: none;
  text-overflow: ellipsis;
  white-space: nowrap;
}

// Sin valor, el texto de ejemplo va en gris, como en los demás campos
.app-popup-field__control--empty .app-popup-field__value {
  color: $color-text-secondary;
}

// Quasar deja un margen alrededor de los diálogos; el panel inferior va de borde a borde
:global(.app-popup-sheet .q-dialog__inner) {
  padding: 0;
}

// El panel inferior ocupa el ancho y tiene las esquinas de arriba redondeadas
.app-popup-field__sheet {
  width: 100vw;
  max-width: 100vw;
  overflow: hidden;
  border-radius: $radius-card $radius-card 0 0;
}
</style>
