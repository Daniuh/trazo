<!-- Grilla editable del modo Personalizar (portátil y monitor): arrastrar para mover, la manija de la esquina para
     cambiar el ancho dentro de los topes y el menú ⋯ para hacer lo mismo con el teclado. Es el único lugar que usa
     grid-layout-plus; sus estilos van aquí con tokens, porque se carga sin los que la librería inyecta (ver ADR-0050).
     Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import GridItem from 'grid-layout-plus/es/components/grid-item.vue.mjs'
import GridLayout from 'grid-layout-plus/es/components/grid-layout.vue.mjs'
import type { LayoutItem } from 'grid-layout-plus'
import { computed, ref, watchEffect } from 'vue'
import { layout as layoutTokens } from '@/design/tokens/layout'
import type { MenuItem } from '@/shared/ui/menu'
import type { useDashboardEditor } from '../../application/useDashboardEditor'
import type { Direction, Dimension } from '../../domain/layoutEditing'
import type { DashboardWidget, Metrics, TrendDays, WidgetCode } from '../../domain/dashboard'
import EditableWidget from './EditableWidget.vue'

const props = defineProps<{ editor: ReturnType<typeof useDashboardEditor>; metrics: Metrics }>()
const trendDays = defineModel<TrendDays>('trendDays', { required: true })

// Cada widget con su copia para la librería. La librería mueve las copias en el lugar mientras se arrastra (por eso
// son reactivas, para que los demás widgets se corran en vivo); al soltar, el borrador toma los lugares nuevos
const cells = ref<{ widget: DashboardWidget; item: LayoutItem }[]>([])
watchEffect(() => {
  cells.value = props.editor.visible.value.map((widget) => ({
    widget,
    item: {
      i: widget.code,
      x: widget.x,
      y: widget.y,
      w: widget.w,
      h: widget.h,
      minW: widget.limits.minW,
      maxW: Math.min(widget.limits.maxW, props.editor.columns.value),
      // El alto lo pone el contenido fuera de Personalizar: aquí solo se cambia el ancho
      minH: widget.h,
      maxH: widget.h,
    },
  }))
})
const items = computed<LayoutItem[]>(() => cells.value.map((cell) => cell.item))

const MOVES: [Direction, string][] = [
  ['up', 'Mover arriba'],
  ['down', 'Mover abajo'],
  ['left', 'Mover a la izquierda'],
  ['right', 'Mover a la derecha'],
]
const SIZES: [Dimension, 1 | -1, string][] = [
  ['w', 1, 'Más ancho'],
  ['w', -1, 'Más angosto'],
]

function actionsFor(code: WidgetCode): MenuItem[] {
  const { canMove, canResize } = props.editor
  return [
    ...MOVES.map(([direction, label]) => ({
      key: `move:${direction}`,
      label,
      disabled: !canMove(code, direction),
    })),
    ...SIZES.map(([dimension, delta, label], index) => ({
      key: `size:${dimension}:${delta}`,
      label,
      disabled: !canResize(code, dimension, delta),
      separated: index === 0,
    })),
    { key: 'remove', label: 'Quitar del inicio', icon: 'trash', danger: true, separated: true },
  ]
}

function run(code: WidgetCode, key: string): void {
  const [kind, first, second] = key.split(':')
  if (kind === 'move') props.editor.move(code, first as Direction)
  if (kind === 'size') props.editor.resize(code, first as Dimension, Number(second) as 1 | -1)
  if (kind === 'remove') props.editor.remove(code)
}

const place = (layout: LayoutItem[]) => props.editor.place(layout)
</script>

<template>
  <GridLayout
    class="dashboard-editor"
    :layout="items"
    :col-num="editor.columns.value"
    :row-height="layoutTokens.dashboardRowHeight"
    :margin="[layoutTokens.dashboardGap, layoutTokens.dashboardGap]"
    :max-rows="200"
    vertical-compact
    @layout-updated="place"
  >
    <GridItem
      v-for="{ widget, item } in cells"
      :key="widget.code"
      v-bind="item"
      drag-ignore-from=".editable-widget__menu, .vgl-item__resizer"
    >
      <EditableWidget
        v-model:trend-days="trendDays"
        :widget="widget"
        :metrics="metrics"
        :actions="actionsFor(widget.code)"
        @action="run(widget.code, $event)"
      />
    </GridItem>
  </GridLayout>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Estilos que la librería trae y no se cargan: posición, transiciones, sombra al arrastrar y zona de destino
.dashboard-editor {
  position: relative;
  transition: height $duration-base ease;
}

.dashboard-editor :deep(.vgl-item) {
  position: absolute;
  box-sizing: border-box;
  transition: transform $duration-base ease;
}

.dashboard-editor :deep(.vgl-item--transform) {
  right: auto;
  left: 0;
}

.dashboard-editor :deep(.vgl-item--no-touch) {
  touch-action: none;
}

.dashboard-editor :deep(.vgl-item--dragging),
.dashboard-editor :deep(.vgl-item--resizing) {
  z-index: 3;
  border-radius: $radius-card;
  box-shadow: $shadow-dragging;
  transition: none;
  user-select: none;
}

.dashboard-editor :deep(.vgl-item--dragging .editable-widget) {
  cursor: grabbing;
}

.dashboard-editor :deep(.vgl-item--placeholder) {
  z-index: 2;
  border: 2px dashed $dashboard-edit-border;
  border-radius: $radius-card;
  background: $dashboard-drop-zone;
  transition-duration: $duration-fast;
  user-select: none;
}

.dashboard-editor :deep(.vgl-item__resizer) {
  position: absolute;
  right: 0;
  bottom: 0;
  z-index: 1;
  width: $dashboard-resize-handle;
  height: $dashboard-resize-handle;
  cursor: se-resize;

  &::before {
    position: absolute;
    inset: 0 $space-1 $space-1 0;
    border-right: 2px solid $dashboard-edit-border;
    border-bottom: 2px solid $dashboard-edit-border;
    border-bottom-right-radius: 2px;
    content: '';
  }
}
</style>
