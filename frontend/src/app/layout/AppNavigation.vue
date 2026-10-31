<!-- Lista de navegación del menú lateral. En modo compacto (tablet) muestra solo íconos con su nombre al pasar. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/layout.md -->
<script setup lang="ts">
import { QTooltip } from 'quasar'
import AppIcon from '@/shared/ui/AppIcon.vue'
import type { NavItem } from '../navigation'

defineProps<{ items: NavItem[]; compact?: boolean }>()
defineEmits<{ navigate: [] }>()
</script>

<template>
  <nav class="app-nav" aria-label="Menú principal">
    <RouterLink
      v-for="item in items"
      :key="item.label"
      :to="item.to"
      class="app-nav__item"
      :active-class="item.exact ? '' : 'app-nav__item--active'"
      exact-active-class="app-nav__item--active"
      :aria-label="compact ? item.label : undefined"
      @click="$emit('navigate')"
    >
      <AppIcon :name="item.icon" />
      <span v-if="!compact">{{ item.label }}</span>
      <QTooltip v-else anchor="center right" self="center left">{{ item.label }}</QTooltip>
    </RouterLink>
  </nav>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-nav {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  padding: $space-3 $space-2;
}

.app-nav__item {
  position: relative;
  display: flex;
  align-items: center;
  gap: $space-3;
  min-height: $touch-target;
  padding: 0 $space-3;
  border-radius: $radius-control;
  color: $color-text-secondary;
  font-size: $font-size-md;
  text-decoration: none;

  &:hover {
    background: $color-page;
    color: $color-text;
  }
}

// Activo: fondo neutro y barra azul; nunca fondo tintado con texto del mismo color
.app-nav__item--active {
  background: $color-border;
  color: $color-text;
  font-weight: $font-weight-medium;

  &::before {
    position: absolute;
    top: $space-2;
    bottom: $space-2;
    left: 0;
    width: 3px;
    border-radius: $radius-pill;
    background: $color-primary;
    content: '';
  }
}
</style>
