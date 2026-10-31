<!-- Campo de texto. Une la envoltura AppField con el input de Quasar y su accesibilidad. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QInput } from 'quasar'
import { useId } from 'vue'
import AppField from './AppField.vue'

defineProps<{
  label: string
  info?: string
  error?: string
  required?: boolean
  type?: 'text' | 'email' | 'password' | 'textarea' | 'search'
  placeholder?: string
  maxlength?: number
  autocomplete?: string
}>()

const model = defineModel<string>({ default: '' })
const id = useId()
</script>

<template>
  <AppField :id="id" :label="label" :info="info" :error="error" :required="required">
    <QInput
      v-model="model"
      :for="id"
      :type="type ?? 'text'"
      :placeholder="placeholder"
      :maxlength="maxlength"
      :autocomplete="autocomplete"
      :autogrow="type === 'textarea'"
      :aria-invalid="Boolean(error)"
      :aria-describedby="error ? `${id}-error` : undefined"
      outlined
      dense
      hide-bottom-space
    />
  </AppField>
</template>
