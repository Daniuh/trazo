<!-- Campo para un código numérico de un solo uso (p. ej. los 6 dígitos de la aplicación de autenticación).
     Un solo campo, no una casilla por dígito: funciona con pegar, con el autocompletado del teléfono y con lectores de
     pantalla. Teclado numérico en el celular y solo acepta dígitos. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QInput } from 'quasar'
import { useId } from 'vue'
import AppField from './AppField.vue'

const props = withDefaults(defineProps<{ label: string; error?: string; length?: number; autofocus?: boolean }>(), {
  error: undefined,
  length: 6,
  autofocus: false,
})

const model = defineModel<string>({ default: '' })
const id = useId()

// Pegar "123 456" o "123-456" funciona: se quedan solo los dígitos
function update(value: string | number | null): void {
  model.value = String(value ?? '')
    .replace(/\D/g, '')
    .slice(0, props.length)
}
</script>

<template>
  <AppField :id="id" :label="label" :error="error">
    <QInput
      :model-value="model"
      :for="id"
      class="app-code-field"
      type="text"
      inputmode="numeric"
      autocomplete="one-time-code"
      :maxlength="length"
      :placeholder="'0'.repeat(length)"
      :autofocus="autofocus"
      :aria-invalid="Boolean(error)"
      :aria-describedby="error ? `${id}-error` : undefined"
      outlined
      hide-bottom-space
      @update:model-value="update"
    />
  </AppField>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-code-field :deep(.q-field__native) {
  font-size: $font-size-xl;
  font-variant-numeric: tabular-nums;
  letter-spacing: $letter-spacing-code;
  text-align: center;
}
</style>
