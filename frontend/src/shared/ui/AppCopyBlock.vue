<!-- Texto para copiar (una clave, un código): se muestra legible y con el botón "Copiar", que avisa al terminar.
     Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { ref } from 'vue'
import AppIcon from './AppIcon.vue'

const props = defineProps<{ value: string; label: string; display?: string }>()

const copied = ref(false)
let timer: ReturnType<typeof setTimeout> | undefined

async function copy(): Promise<void> {
  try {
    await navigator.clipboard.writeText(props.value)
    copied.value = true
    clearTimeout(timer)
    timer = setTimeout(() => (copied.value = false), 2000)
  } catch {
    // Sin permiso para el portapapeles, el texto sigue visible para copiarlo a mano
  }
}
</script>

<template>
  <div class="app-copy-block">
    <code class="app-copy-block__value">{{ display ?? value }}</code>
    <button class="app-copy-block__button" type="button" :aria-label="`Copiar ${label}`" @click="copy">
      <AppIcon :name="copied ? 'check' : 'copy'" size="sm" />
      <span aria-live="polite">{{ copied ? 'Copiado' : 'Copiar' }}</span>
    </button>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-copy-block {
  display: flex;
  align-items: center;
  gap: $space-2;
  padding: $space-2 $space-2 $space-2 $space-3;
  border: 1px solid $color-border;
  border-radius: $radius-control;
  background: $color-page;
}

.app-copy-block__value {
  flex: 1;
  min-width: 0;
  font-family: inherit;
  font-size: $font-size-md;
  font-variant-numeric: tabular-nums;
  letter-spacing: $letter-spacing-code;
  overflow-wrap: anywhere;
}

.app-copy-block__button {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  gap: $space-1;
  min-height: $touch-target;
  padding: 0 $space-2;
  border: 0;
  border-radius: calc($radius-control - 2px);
  background: none;
  color: $color-primary;
  font: inherit;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  cursor: pointer;

  &:hover {
    background: $color-surface;
  }
}

@include laptop {
  .app-copy-block__button {
    min-height: 2rem;
  }
}
</style>
