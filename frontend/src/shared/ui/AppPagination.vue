<!-- Paginación: "1–20 de 534", páginas con saltos (…) y tamaño de página. En celular solo anterior, "Página 3 de 27" y siguiente.
     Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'
import AppSelect from './form/AppSelect.vue'
import { useViewport } from './useViewport'

const props = defineProps<{
  page: number
  lastPage: number
  total: number
  perPage: number
  perPageOptions: readonly number[]
}>()
const emit = defineEmits<{ 'update:page': [page: number]; 'update:perPage': [perPage: number] }>()

const { isMobile } = useViewport()
const numberFormat = new Intl.NumberFormat('es-CO')

const range = computed(() => {
  if (props.total === 0) return 'Sin resultados'
  const first = (props.page - 1) * props.perPage + 1
  const last = Math.min(props.page * props.perPage, props.total)
  return `${numberFormat.format(first)}–${numberFormat.format(last)} de ${numberFormat.format(props.total)}`
})

// Primera, última y las vecinas de la actual; el resto se resume con "…"
const pages = computed<(number | null)[]>(() => {
  const wanted = new Set([1, props.lastPage, props.page - 1, props.page, props.page + 1])
  const sorted = [...wanted].filter((page) => page >= 1 && page <= props.lastPage).sort((a, b) => a - b)
  return sorted.flatMap((page, index) =>
    index > 0 && page - (sorted[index - 1] as number) > 1 ? [null, page] : [page],
  )
})

const perPageModel = computed({
  get: () => props.perPage,
  set: (value: number | number[] | null) => emit('update:perPage', Number(value)),
})
const sizeOptions = computed(() => props.perPageOptions.map((size) => ({ label: `${size} por página`, value: size })))
</script>

<template>
  <nav class="app-pagination" aria-label="Paginación">
    <p class="app-pagination__range" aria-live="polite">{{ range }}</p>

    <div class="app-pagination__pages">
      <button
        class="app-pagination__button"
        type="button"
        aria-label="Página anterior"
        :disabled="page <= 1"
        @click="emit('update:page', page - 1)"
      >
        <AppIcon name="chevron-left" size="sm" />
      </button>

      <span v-if="isMobile" class="app-pagination__current">Página {{ page }} de {{ lastPage }}</span>
      <template v-else>
        <template v-for="(item, index) in pages" :key="index">
          <span v-if="item === null" class="app-pagination__gap" aria-hidden="true">…</span>
          <button
            v-else
            class="app-pagination__button"
            :class="{ 'app-pagination__button--current': item === page }"
            type="button"
            :aria-label="`Página ${item}`"
            :aria-current="item === page ? 'page' : undefined"
            @click="emit('update:page', item)"
          >
            {{ item }}
          </button>
        </template>
      </template>

      <button
        class="app-pagination__button"
        type="button"
        aria-label="Página siguiente"
        :disabled="page >= lastPage"
        @click="emit('update:page', page + 1)"
      >
        <AppIcon name="chevron-right" size="sm" />
      </button>
    </div>

    <AppSelect v-model="perPageModel" class="app-pagination__size" label="Tamaño de página" :options="sizeOptions" />
  </nav>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
}

.app-pagination__range,
.app-pagination__current {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

.app-pagination__pages {
  display: flex;
  align-items: center;
  gap: $space-1;
}

.app-pagination__button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: $touch-target;
  min-height: $touch-target;
  border: 1px solid transparent;
  border-radius: $radius-control;
  background: none;
  color: $color-text;
  font: inherit;
  font-size: $font-size-sm;
  cursor: pointer;

  &:hover:not(:disabled) {
    background: $color-border;
  }

  &:disabled {
    color: $color-muted;
    cursor: not-allowed;
  }
}

.app-pagination__button--current {
  border-color: $color-primary;
  background: $color-primary;
  color: $color-on-dark;
  font-weight: $font-weight-semibold;

  &:hover:not(:disabled) {
    background: $color-primary;
  }
}

.app-pagination__gap {
  padding: 0 $space-1;
  color: $color-text-secondary;
}

// La etiqueta del tamaño solo la leen los lectores de pantalla; el texto de cada opción ya lo dice
.app-pagination__size :deep(.app-field__label) {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip-path: inset(50%);
}

@include laptop {
  .app-pagination__button {
    min-width: 2.25rem;
    min-height: 2.25rem;
  }
}
</style>
