<!-- Enlace para volver al listado de una sección ("‹ Usuarios" en la ficha de una persona). Va arriba del título, con
     la flecha alineada al borde del contenido. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { RouterLink, type RouteLocationRaw } from 'vue-router'
import AppIcon from './AppIcon.vue'

defineProps<{ to: RouteLocationRaw; label: string }>()
</script>

<template>
  <RouterLink class="app-back-link" :to="to" :aria-label="`Volver a ${label}`">
    <AppIcon name="chevron-left" size="sm" />
    {{ label }}
  </RouterLink>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// La flecha se corre hacia afuera para que el texto quede alineado con el título de abajo
.app-back-link {
  display: inline-flex;
  align-self: flex-start;
  align-items: center;
  gap: $space-1;
  min-height: $touch-target;
  margin-left: calc(#{$space-2} * -1);
  padding: 0 $space-3 0 $space-1;
  border-radius: $radius-control;
  color: $color-text-secondary;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  text-decoration: none;

  &:hover {
    background: $color-border;
    color: $color-text;
  }
}

// Con mouse puede ser tan compacto como un botón
@include laptop {
  .app-back-link {
    min-height: $control-height-compact;
  }
}
</style>
