// Estado del diálogo de nota: quien lo abre dice qué se pide (título, si la nota es obligatoria) y qué hacer con ella.
// ask() espera hasta que la acción sale bien o se cancela. Documentado en docs/frontend/incidents.md
import { ref, shallowRef } from 'vue'

export interface NoteRequest {
  title: string
  message?: string
  submitLabel: string
  label: string
  // Obligatoria (la solución, un motivo) u opcional (los demás cambios de estado)
  required: boolean
  danger?: boolean
  // Ejecuta la acción con la nota; true si salió bien
  run: (note: string | null) => Promise<boolean>
}

export function useNoteDialog() {
  const open = ref(false)
  const request = shallowRef<NoteRequest | null>(null)
  let settle: ((done: boolean) => void) | null = null

  function ask(next: NoteRequest): Promise<boolean> {
    settle?.(false)
    request.value = next
    open.value = true
    return new Promise((resolve) => {
      settle = resolve
    })
  }

  function finish(done: boolean): void {
    open.value = false
    settle?.(done)
    settle = null
  }

  return { open, request, ask, finish }
}
