<!-- Selector de una o varias opciones, con la misma envoltura que el resto de campos. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts" generic="T extends string | number">
import { QSelect } from 'quasar'
import { useId } from 'vue'
import AppIcon from '../AppIcon.vue'
import AppField from './AppField.vue'
import type { SelectOption } from './types'

defineProps<{
  label: string
  options: SelectOption<T>[]
  info?: string
  error?: string
  required?: boolean
  multiple?: boolean
  placeholder?: string
}>()

const model = defineModel<T | T[] | null>({ default: null })
const id = useId()
</script>

<template>
  <AppField :id="id" :label="label" :info="info" :error="error" :required="required">
    <QSelect
      v-model="model"
      :for="id"
      :options="options"
      :multiple="multiple"
      :placeholder="placeholder"
      :aria-invalid="Boolean(error)"
      :aria-describedby="error ? `${id}-error` : undefined"
      emit-value
      map-options
      outlined
      dense
      hide-bottom-space
      options-dense
      hide-dropdown-icon
    >
      <!-- Flecha propia de Trazo en lugar del ícono de Quasar -->
      <template #append><AppIcon name="chevron-down" size="sm" /></template>
    </QSelect>
  </AppField>
</template>
