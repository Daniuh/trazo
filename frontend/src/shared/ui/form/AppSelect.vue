<!-- Selector de una o varias opciones, con la misma envoltura que el resto de campos. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts" generic="T extends string | number">
import { QItem, QSelect } from 'quasar'
import { computed, ref, useId } from 'vue'
import AppIcon from '../AppIcon.vue'
import AppField from './AppField.vue'
import type { SelectOption } from './types'

const props = defineProps<{
  label: string
  options: SelectOption<T>[]
  info?: string
  error?: string
  required?: boolean
  multiple?: boolean
  placeholder?: string
  // Para listas largas (p. ej. personas): se escribe para filtrar, sin importar tildes ni mayúsculas
  filterable?: boolean
  // Etiqueta al lado en vez de arriba (barras de una fila)
  inline?: boolean
}>()

const model = defineModel<T | T[] | null>({ default: null })
const id = useId()

const query = ref('')
const plain = (text: string) =>
  text
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLowerCase()
const visibleOptions = computed(() =>
  props.filterable && query.value
    ? props.options.filter((option) => plain(option.label).includes(plain(query.value)))
    : props.options,
)

function onFilter(value: string, update: (apply: () => void) => void): void {
  update(() => {
    query.value = value
  })
}

// El selector de Quasar no muestra el texto de ejemplo cuando está vacío: se muestra aquí, en gris
const isEmpty = computed(() => model.value === null || (Array.isArray(model.value) && model.value.length === 0))
// Mientras llegan las opciones (catálogos), no se muestra el código interno del valor elegido
const isPending = computed(() => {
  const values = Array.isArray(model.value) ? model.value : [model.value]
  return !isEmpty.value && !values.some((value) => props.options.some((option) => option.value === value))
})
const displayValue = computed(() => (isEmpty.value ? props.placeholder : isPending.value ? '' : undefined))
</script>

<template>
  <AppField :id="id" :label="label" :info="info" :error="error" :required="required" :inline="inline">
    <QSelect
      v-model="model"
      :class="{ 'app-select--empty': isEmpty, 'app-select--filterable': filterable }"
      :for="id"
      :options="visibleOptions"
      :multiple="multiple"
      :use-input="filterable"
      input-debounce="0"
      :display-value="displayValue"
      :aria-invalid="Boolean(error)"
      :aria-describedby="error ? `${id}-error` : undefined"
      emit-value
      map-options
      outlined
      dense
      hide-bottom-space
      options-dense
      hide-dropdown-icon
      popup-content-class="app-select-menu"
      @filter="onFilter"
    >
      <!-- Flecha propia de Trazo en lugar del ícono de Quasar -->
      <template #append><AppIcon name="chevron-down" size="sm" /></template>
      <!-- Opciones con el estilo común de los menús; la elegida va en negrita y con ✓ -->
      <template #option="{ itemProps, opt, selected }">
        <QItem
          v-bind="itemProps"
          class="app-select-menu__option"
          :class="{ 'app-select-menu__option--selected': selected }"
        >
          <span class="app-select-menu__label">{{ opt.label }}</span>
          <AppIcon v-if="selected" name="check" size="sm" />
        </QItem>
      </template>
    </QSelect>
  </AppField>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-select--empty :deep(.q-field__native) {
  color: $color-text-secondary;
}

// Con búsqueda: lo elegido y la caja para escribir van en una sola línea, para que el campo nunca crezca
.app-select--filterable :deep(.q-field__native) {
  flex-wrap: nowrap;
}

.app-select--filterable :deep(.q-select__selected-value) {
  flex: 0 1 auto;
  min-width: 0;
}

.app-select--filterable :deep(.q-field__input) {
  flex: 1 1 0;
  min-width: 0;
}

// Con la lista abierta se escribe sobre el campo limpio: lo elegido se oculta y la caja toma todo el ancho
.app-select--filterable :deep(.q-field__native:has(input[aria-expanded='true']) .q-select__selected-value) {
  display: none;
}
</style>

<!-- La lista se abre fuera del componente (Quasar la lleva al final de la página): sus estilos no pueden ser scoped -->
<style lang="scss">
@use '@/design/tokens' as *;

.app-select-menu {
  padding: $space-1 0;

  // Sin la capa de color de Quasar al pasar el cursor
  .q-focus-helper {
    display: none;
  }
}

.app-select-menu__option {
  @include menu-item;

  // Opción marcada con el teclado: el mismo fondo que al pasar el cursor
  &.q-manual-focusable--focused {
    background: $color-page;
  }
}

.app-select-menu__label {
  flex: 1;
}

// La elegida: en negrita y con ✓, sin texto de color sobre fondo tintado
.app-select-menu__option--selected {
  font-weight: $font-weight-semibold;
}
</style>
