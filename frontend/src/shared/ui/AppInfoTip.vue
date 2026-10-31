<!-- Ícono de información: guarda una ayuda que aparece al pasar el cursor, al enfocarlo o al tocarlo. -->
<!-- Nunca se usa para errores ni para instrucciones imprescindibles. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { onBeforeUnmount, reactive, ref, useId } from 'vue'
import AppIcon from './AppIcon.vue'

const props = defineProps<{ text: string; label?: string }>()

// Margen mínimo entre la burbuja y el borde de la pantalla
const EDGE_GAP = 8

const open = ref(false)
const tipId = useId()
const root = ref<HTMLElement>()
const bubble = ref<HTMLElement>()
const position = reactive({ top: 0, left: 0 })

// La burbuja va debajo del ícono, centrada, pero nunca se sale de la pantalla
function place() {
  const trigger = root.value?.getBoundingClientRect()
  const width = bubble.value?.offsetWidth ?? 0
  if (!trigger) return

  const centered = trigger.left + trigger.width / 2 - width / 2
  position.top = trigger.bottom + EDGE_GAP
  position.left = Math.min(Math.max(centered, EDGE_GAP), window.innerWidth - width - EDGE_GAP)
}

// En pantallas táctiles no existe "pasar el cursor": se abre al tocar y se cierra al tocar fuera
function closeOnOutside(event: Event) {
  if (!root.value?.contains(event.target as Node)) setOpen(false)
}

function setOpen(value: boolean) {
  open.value = value
  if (value) place()
  const toggleListener = value ? document.addEventListener : document.removeEventListener
  toggleListener.call(document, 'click', closeOnOutside)
}

onBeforeUnmount(() => document.removeEventListener('click', closeOnOutside))
</script>

<template>
  <span
    ref="root"
    class="info-tip"
    :class="{ 'info-tip--open': open }"
    @mouseenter="place"
    @focusin="place"
    @keydown.esc="setOpen(false)"
  >
    <button
      class="info-tip__trigger"
      type="button"
      :aria-label="props.label ?? 'Más información'"
      :aria-describedby="tipId"
      :aria-expanded="open"
      @click="setOpen(!open)"
    >
      <AppIcon name="info" size="sm" />
    </button>
    <span
      :id="tipId"
      ref="bubble"
      class="info-tip__bubble"
      role="tooltip"
      :style="{ top: `${position.top}px`, left: `${position.left}px` }"
    >
      {{ text }}
    </span>
  </span>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.info-tip {
  display: inline-flex;
  vertical-align: middle;
}

.info-tip__trigger {
  display: inline-flex;
  padding: $space-1;
  margin: -$space-1;
  border: 0;
  border-radius: 50%;
  background: none;
  color: $color-text-secondary;
  cursor: pointer;
}

.info-tip__bubble {
  position: fixed;
  z-index: 6000;
  width: max-content;
  max-width: min(16rem, calc(100vw - #{$space-4}));
  padding: $space-2 $space-3;
  border-radius: $radius-control;
  background: $color-text;
  color: $color-on-dark;
  font-size: $font-size-xs;
  font-weight: $font-weight-regular;
  line-height: $line-height-base;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity $duration-fast;
}

.info-tip:hover .info-tip__bubble,
.info-tip:focus-within .info-tip__bubble,
.info-tip--open .info-tip__bubble {
  opacity: 1;
  visibility: visible;
}
</style>
