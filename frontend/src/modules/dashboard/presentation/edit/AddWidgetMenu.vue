<!-- Botón "Agregar widget" del modo Personalizar: abre la lista de widgets ocultos con su nombre y qué muestran.
     El elegido entra al final del dashboard. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { ref } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppMenu from '@/shared/ui/AppMenu.vue'
import type { DashboardWidget, WidgetCode } from '../../domain/dashboard'

defineProps<{ widgets: DashboardWidget[] }>()
const emit = defineEmits<{ add: [code: WidgetCode] }>()

// Se cierra a mano: al elegir, la opción desaparece de la lista antes de que el menú alcance a cerrarse solo
const open = ref(false)

function add(code: WidgetCode): void {
  open.value = false
  emit('add', code)
}
</script>

<template>
  <AppButton variant="secondary" icon="plus">
    Agregar widget
    <AppMenu v-model="open">
      <div class="add-widget" role="menu" aria-label="Widgets para agregar">
        <p v-if="widgets.length === 0" class="add-widget__empty">Todos los widgets ya están en tu inicio.</p>
        <button
          v-for="widget in widgets"
          :key="widget.code"
          class="add-widget__item"
          type="button"
          role="menuitem"
          @click="add(widget.code)"
        >
          <span class="add-widget__name">{{ widget.name }}</span>
          <span class="add-widget__description">{{ widget.description }}</span>
        </button>
      </div>
    </AppMenu>
  </AppButton>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.add-widget {
  display: flex;
  flex-direction: column;
  width: $menu-wide-width;
  max-width: 100%;
  padding: $space-1 0;
}

.add-widget__empty {
  margin: 0;
  padding: $space-3 $space-4;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

.add-widget__item {
  @include menu-item;

  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
}

.add-widget__name {
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.add-widget__description {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}
</style>
