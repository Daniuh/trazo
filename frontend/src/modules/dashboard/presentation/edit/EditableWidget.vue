<!-- Widget en el modo Personalizar: muestra sus datos reales (sin enlaces activos) con un marco punteado,
     la agarradera para arrastrarlo, el menú ⋯ con las mismas acciones para quien usa el teclado y, mientras
     se cambia el ancho, la medida en columnas (el alto lo pone el contenido).
     Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppActionMenu from '@/shared/ui/AppActionMenu.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import type { MenuItem } from '@/shared/ui/menu'
import type { DashboardWidget, Metrics, TrendDays } from '../../domain/dashboard'
import WidgetContent from '../widgets/WidgetContent.vue'

const props = defineProps<{ widget: DashboardWidget; metrics: Metrics; actions: MenuItem[] }>()
const emit = defineEmits<{ action: [key: string] }>()
const trendDays = defineModel<TrendDays>('trendDays', { required: true })

const size = computed(() => `${props.widget.w} columnas`)
// Con teclado no se ve la medida: el nombre del botón la dice
const menuLabel = computed(() => `Opciones de ${props.widget.name}, ${props.widget.w} columnas de ancho`)
</script>

<template>
  <div class="editable-widget">
    <!-- Solo vista: los enlaces y botones del widget no responden mientras se edita -->
    <div class="editable-widget__preview" inert>
      <WidgetContent v-model:trend-days="trendDays" :widget="widget" :metrics="metrics" />
    </div>

    <!-- Medida en columnas, visible solo mientras se cambia el ancho -->
    <span class="editable-widget__size" aria-hidden="true">{{ size }}</span>

    <div class="editable-widget__controls">
      <span class="editable-widget__handle" title="Arrastra para mover">
        <AppIcon name="grip" size="sm" />
      </span>
      <button class="editable-widget__menu" type="button" :aria-label="menuLabel">
        <AppIcon name="more" size="sm" />
        <AppActionMenu
          :items="actions"
          :label="`Opciones de ${widget.name}`"
          align="right"
          @select="emit('action', $event)"
        />
      </button>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.editable-widget {
  position: relative;
  height: 100%;
  border-radius: $radius-card;
  outline: 2px dashed $dashboard-edit-border;
  outline-offset: -1px;
  cursor: grab;
}

.editable-widget__preview {
  height: 100%;
  user-select: none;
}

// Sobre la esquina del encabezado, tapando el ícono de información, que no responde mientras se edita
.editable-widget__controls {
  position: absolute;
  top: $space-2;
  right: $space-2;
  display: flex;
  align-items: center;
  gap: $space-1;
  padding: 2px;
  border: 1px solid $color-border;
  border-radius: $radius-control;
  background: $color-surface;
}

.editable-widget__size {
  position: absolute;
  top: 50%;
  left: 50%;
  display: none;
  padding: $space-1 $space-3;
  border-radius: $radius-pill;
  background: $color-neutral-dark;
  color: $color-on-dark;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  font-variant-numeric: tabular-nums;
  transform: translate(-50%, -50%);
}

:global(.vgl-item--resizing) .editable-widget__size {
  display: block;
}

.editable-widget__handle,
.editable-widget__menu {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: calc($radius-control - 2px);
  color: $color-text;
}

.editable-widget__handle {
  cursor: grab;
}

.editable-widget__menu {
  border: 0;
  background: none;
  cursor: pointer;

  &:hover {
    background: $color-page;
  }

  &:focus-visible {
    outline: none;
    box-shadow: $focus-ring;
  }
}
</style>
