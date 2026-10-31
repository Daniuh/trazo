<!-- Campo de fecha y hora con el diseño de Trazo (no el del navegador). Se ve como los demás selectores.
     Portátil y monitor: menú bajo el campo. Celular y tablet: panel que sube desde abajo.
     El valor es ISO en UTC o null. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QDialog, QField, QMenu } from 'quasar'
import { computed, ref, useId } from 'vue'
import { formatShortDateTime } from '@/shared/format/dates'
import AppIcon from '../AppIcon.vue'
import { useViewport } from '../useViewport'
import AppField from './AppField.vue'
import DateTimePanel from './DateTimePanel.vue'

const props = defineProps<{
  label: string
  info?: string
  error?: string
  required?: boolean
  placeholder?: string
  // Fecha en la que abre el calendario si el campo está vacío
  suggestion?: string | null
  // Si se indica, el pie ofrece vaciar el campo con este texto (p. ej. "Usar la sugerida")
  clearLabel?: string
}>()

const model = defineModel<string | null>({ default: null })
const id = useId()
const open = ref(false)
const { isLaptopUp } = useViewport()

const text = computed(() => (model.value ? formatShortDateTime(model.value) : (props.placeholder ?? '')))

function openSheet(): void {
  if (!isLaptopUp.value) open.value = true
}

function close(value?: string | null): void {
  if (value !== undefined) model.value = value
  open.value = false
}
</script>

<template>
  <AppField :id="id" :label="label" :info="info" :error="error" :required="required">
    <!-- En portátil y monitor el menú se abre solo (clic o Enter sobre el campo); en pantallas chicas lo abre este contenedor -->
    <div class="app-datetime__trigger" @click="openSheet">
      <QField
        class="app-datetime"
        :class="{ 'app-datetime--empty': !model }"
        :model-value="model"
        outlined
        dense
        hide-bottom-space
      >
        <template #control>
          <div
            :id="id"
            class="app-datetime__value"
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
        <template #append><AppIcon name="calendar" size="sm" /></template>
      </QField>

      <!-- El menú se ancla a este contenedor: QField no muestra lo que se pone dentro de él -->
      <QMenu v-if="isLaptopUp" v-model="open" anchor="bottom left" self="top left" :offset="[0, 6]">
        <DateTimePanel
          :title="label"
          :value="model"
          :suggestion="suggestion ?? null"
          :clear-label="clearLabel"
          @confirm="close"
          @clear="close(null)"
          @cancel="close()"
        />
      </QMenu>
    </div>

    <QDialog v-if="!isLaptopUp" v-model="open" position="bottom" full-width class="app-datetime-sheet">
      <DateTimePanel
        class="app-datetime__sheet"
        :title="label"
        :value="model"
        :suggestion="suggestion ?? null"
        :clear-label="clearLabel"
        @confirm="close"
        @clear="close(null)"
        @cancel="close()"
      />
    </QDialog>
  </AppField>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-datetime {
  cursor: pointer;
}

.app-datetime__value {
  overflow: hidden;
  align-self: center;
  width: 100%;
  outline: none;
  text-overflow: ellipsis;
  white-space: nowrap;
}

// Sin valor, el texto de ejemplo va en gris, como en los demás campos
.app-datetime--empty .app-datetime__value {
  color: $color-text-secondary;
}

// Quasar deja un margen alrededor de los diálogos; el panel inferior va de borde a borde
:global(.app-datetime-sheet .q-dialog__inner) {
  padding: 0;
}

// El panel inferior ocupa el ancho y tiene las esquinas de arriba redondeadas
.app-datetime__sheet {
  width: 100vw;
  max-width: 100vw;
  border-radius: $radius-card $radius-card 0 0;
}
</style>
