// Modo Personalizar: un borrador del diseño que se edita sin tocar lo guardado hasta pulsar "Guardar".
// Sabe si hay cambios, guarda o restaura el original en el servidor y avisa cuál es el diseño que quedó.
// Las confirmaciones las pide la pantalla. Documentado en docs/frontend/dashboard.md
import { computed, ref, shallowRef, type Ref } from 'vue'
import { ApiError, toApiError } from '@/shared/http'
import type { DashboardLayout, DashboardWidget, WidgetCode } from '../domain/dashboard'
import * as rules from '../domain/layoutEditing'
import { dashboardApi } from '../infrastructure/dashboardApi'

export function useDashboardEditor(layout: Ref<DashboardLayout | null>, onApplied: (next: DashboardLayout) => void) {
  const editing = ref(false)
  const draft = shallowRef<DashboardWidget[]>([])
  const saving = ref(false)
  const restoring = ref(false)
  const error = ref<ApiError | null>(null)

  const columns = computed(() => layout.value?.columns ?? 12)
  const original = computed(() => rules.signature(layout.value?.widgets ?? []))
  const dirty = computed(() => editing.value && rules.signature(draft.value) !== original.value)
  const visible = computed(() => draft.value.filter((widget) => widget.visible))
  const hidden = computed(() => draft.value.filter((widget) => !widget.visible))

  function start(): void {
    if (!layout.value) return
    draft.value = layout.value.widgets.map((widget) => ({ ...widget }))
    error.value = null
    editing.value = true
  }

  function stop(): void {
    editing.value = false
    draft.value = []
    error.value = null
  }

  // Si las reglas devuelven el mismo diseño (sin cambios), el shallowRef no avisa y la grilla no se redibuja
  const update = (next: DashboardWidget[]) => (draft.value = next)

  const move = (code: WidgetCode, direction: rules.Direction) =>
    update(rules.move(draft.value, code, direction, columns.value))
  const resize = (code: WidgetCode, dimension: rules.Dimension, delta: 1 | -1) =>
    update(rules.resize(draft.value, code, dimension, delta, columns.value))
  const remove = (code: WidgetCode) => update(rules.remove(draft.value, code))
  const add = (code: WidgetCode) => update(rules.add(draft.value, code, columns.value))
  const place = (boxes: Parameters<typeof rules.place>[1]) => update(rules.place(draft.value, boxes))

  const canMove = (code: WidgetCode, direction: rules.Direction) =>
    rules.canMove(draft.value, code, direction, columns.value)
  const canResize = (code: WidgetCode, dimension: rules.Dimension, delta: 1 | -1) =>
    rules.canResize(draft.value, code, dimension, delta, columns.value)

  // Las dos acciones terminan igual: el diseño que devuelve el servidor reemplaza al anterior
  async function persist(flag: Ref<boolean>, request: () => Promise<DashboardLayout>): Promise<boolean> {
    flag.value = true
    error.value = null
    try {
      onApplied(await request())
      stop()
      return true
    } catch (raw) {
      error.value = toApiError(raw)
      return false
    } finally {
      flag.value = false
    }
  }

  const save = () => persist(saving, () => dashboardApi.save(draft.value))
  const restore = () => persist(restoring, () => dashboardApi.reset())

  return {
    editing,
    draft,
    visible,
    hidden,
    columns,
    dirty,
    saving,
    restoring,
    error,
    start,
    cancel: stop,
    move,
    resize,
    remove,
    add,
    place,
    canMove,
    canResize,
    save,
    restore,
  }
}
