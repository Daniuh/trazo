<!-- Menú de opciones que se abre desde el botón que lo contiene. Cada opción puede llevar ícono, marcarse como peligrosa,
     quedar desactivada o separarse de las anteriores con una línea. Se cierra solo al elegir y se abre donde cabe (AppMenu). Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { ClosePopup } from 'quasar'
import AppIcon from './AppIcon.vue'
import AppMenu from './AppMenu.vue'
import type { MenuItem } from './menu'

defineProps<{ items: MenuItem[]; label: string; align?: 'left' | 'right' }>()
const emit = defineEmits<{ select: [key: string] }>()

const vClosePopup = ClosePopup
</script>

<template>
  <AppMenu :align="align">
    <div class="app-action-menu" role="menu" :aria-label="label">
      <button
        v-for="item in items"
        :key="item.key"
        v-close-popup
        class="app-action-menu__item"
        :class="{
          'app-action-menu__item--danger': item.danger,
          'app-action-menu__item--separated': item.separated,
        }"
        type="button"
        role="menuitem"
        :disabled="item.disabled"
        @click="emit('select', item.key)"
      >
        <AppIcon v-if="item.icon" :name="item.icon" />
        {{ item.label }}
      </button>
    </div>
  </AppMenu>
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
  @include menu-item;
}

.app-action-menu__item--danger {
  color: $color-danger;
}

.app-action-menu__item--separated {
  border-top: 1px solid $color-border;
}
</style>
