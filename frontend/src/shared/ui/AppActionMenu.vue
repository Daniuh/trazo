<!-- Menú de opciones que se abre desde el botón que lo contiene. Cada opción puede llevar ícono y marcarse como peligrosa.
     Se cierra solo al elegir. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { ClosePopup, QMenu } from 'quasar'
import AppIcon from './AppIcon.vue'
import type { MenuItem } from './menu'

defineProps<{ items: MenuItem[]; label: string; align?: 'left' | 'right' }>()
const emit = defineEmits<{ select: [key: string] }>()

const vClosePopup = ClosePopup
</script>

<template>
  <QMenu
    :anchor="align === 'right' ? 'bottom right' : 'bottom left'"
    :self="align === 'right' ? 'top right' : 'top left'"
    :offset="[0, 6]"
  >
    <div class="app-action-menu" role="menu" :aria-label="label">
      <button
        v-for="item in items"
        :key="item.key"
        v-close-popup
        class="app-action-menu__item"
        :class="{ 'app-action-menu__item--danger': item.danger }"
        type="button"
        role="menuitem"
        @click="emit('select', item.key)"
      >
        <AppIcon v-if="item.icon" :name="item.icon" />
        {{ item.label }}
      </button>
    </div>
  </QMenu>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-action-menu {
  display: flex;
  flex-direction: column;
  min-width: $menu-min-width;
  padding: $space-1 0;
}

.app-action-menu__item {
  display: flex;
  align-items: center;
  gap: $space-2;
  min-height: $touch-target;
  padding: 0 $space-4;
  border: 0;
  background: none;
  color: $color-text;
  font: inherit;
  font-size: $font-size-sm;
  text-align: left;
  cursor: pointer;

  &:hover {
    background: $color-page;
  }
}

.app-action-menu__item--danger {
  color: $color-danger;
}
</style>
