<!-- Campo de texto. Une la envoltura AppField con el input de Quasar y su accesibilidad. -->
<!-- Las contraseñas traen un botón para mostrarlas u ocultarlas. Con "counter" y "maxlength" muestra cuántos caracteres van. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
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
  // Muestra "usados/máximo" bajo el campo; necesita maxlength
  counter?: boolean
  autocomplete?: string
  autofocus?: boolean
  // "numeric": teclado de números en el celular (el valor sigue siendo texto)
  inputmode?: 'numeric'
}>()

const model = defineModel<string>({ default: '' })
const id = useId()

const counterId = `${id}-counter`
const showCounter = computed(() => props.counter && props.maxlength !== undefined)
const describedBy = computed(
  () =>
    [props.error ? `${id}-error` : null, showCounter.value ? counterId : null].filter(Boolean).join(' ') || undefined,
)

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
      :inputmode="inputmode"
      :autogrow="type === 'textarea'"
      :aria-invalid="Boolean(error)"
      :aria-describedby="describedBy"
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
    <p v-if="showCounter" :id="counterId" class="app-text-field__counter">
      {{ model.length }}/{{ maxlength }}<span class="app-text-field__counter-label"> caracteres</span>
    </p>
  </AppField>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Un texto largo empieza con espacio para varias líneas y crece al escribir
// (Quasar fija su propio mínimo para el modo compacto, por eso el selector es más específico)
:deep(.q-textarea.q-field--dense .q-field__native) {
  min-height: $textarea-min-height;
}

.app-text-field__counter {
  margin: $space-1 0 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
  text-align: right;
}

.app-text-field__counter-label {
  @include visually-hidden;
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
