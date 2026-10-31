<!-- Campo de texto. Une la envoltura AppField con el input de Quasar y su accesibilidad. -->
<!-- Las contraseñas traen un botón para mostrarlas u ocultarlas. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QInput } from 'quasar'
import { computed, ref, useId } from 'vue'
import AppIcon from '../AppIcon.vue'
import AppField from './AppField.vue'

const props = defineProps<{
  label: string
  info?: string
  error?: string
  required?: boolean
  type?: 'text' | 'email' | 'password' | 'textarea' | 'search'
  placeholder?: string
  maxlength?: number
  autocomplete?: string
  autofocus?: boolean
}>()

const model = defineModel<string>({ default: '' })
const id = useId()

const isPassword = computed(() => props.type === 'password')
const revealed = ref(false)
const inputType = computed(() => (isPassword.value && revealed.value ? 'text' : (props.type ?? 'text')))
</script>

<template>
  <AppField :id="id" :label="label" :info="info" :error="error" :required="required">
    <QInput
      v-model="model"
      :for="id"
      :type="inputType"
      :placeholder="placeholder"
      :maxlength="maxlength"
      :autocomplete="autocomplete"
      :autofocus="autofocus"
      :autogrow="type === 'textarea'"
      :aria-invalid="Boolean(error)"
      :aria-describedby="error ? `${id}-error` : undefined"
      outlined
      dense
      hide-bottom-space
    >
      <template v-if="isPassword" #append>
        <button
          class="app-text-field__reveal"
          type="button"
          :aria-label="revealed ? 'Ocultar contraseña' : 'Mostrar contraseña'"
          :aria-pressed="revealed"
          @click="revealed = !revealed"
        >
          <AppIcon :name="revealed ? 'eye-off' : 'eye'" />
        </button>
      </template>
    </QInput>
  </AppField>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Un texto largo empieza con espacio para varias líneas y crece al escribir
// (Quasar fija su propio mínimo para el modo compacto, por eso el selector es más específico)
:deep(.q-textarea.q-field--dense .q-field__native) {
  min-height: $textarea-min-height;
}

.app-text-field__reveal {
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
</style>
