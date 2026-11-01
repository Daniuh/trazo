<!-- Campo de fecha y hora con el diseño de Trazo (no el del navegador). Se ve como los demás selectores.
     Portátil y monitor: menú bajo el campo. Celular y tablet: panel que sube desde abajo.
     El valor es ISO en UTC o null. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { formatShortDateTime } from '@/shared/format/dates'
import AppPopupField from './AppPopupField.vue'
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
const open = ref(false)

const text = computed(() => (model.value ? formatShortDateTime(model.value) : (props.placeholder ?? '')))

function close(value?: string | null): void {
  if (value !== undefined) model.value = value
  open.value = false
}
</script>

<template>
  <AppPopupField
    v-model:open="open"
    :label="label"
    :text="text"
    :empty="!model"
    icon="calendar"
    :info="info"
    :error="error"
    :required="required"
  >
    <DateTimePanel
      :title="label"
      :value="model"
      :suggestion="suggestion ?? null"
      :clear-label="clearLabel"
      @confirm="close"
      @clear="close(null)"
      @cancel="close()"
    />
  </AppPopupField>
</template>
