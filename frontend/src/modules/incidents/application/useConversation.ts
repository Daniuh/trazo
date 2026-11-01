// Estado de la conversación de un incidente: los mensajes en orden (el más viejo arriba), cuántos hay, los anteriores
// que faltan por cargar, escribir y borrar. Al llegar un aviso de tiempo real vuelve a pedir lo más reciente y lo une
// con lo que ya tenía. Documentado en docs/frontend/incidents.md
import { ref } from 'vue'
import { ApiError, toApiError } from '@/shared/http'
import type { Comment } from '../domain/incident'
import { conversationApi } from '../infrastructure/conversationApi'

// Une mensajes sin repetir; el id (ULID) ordena por momento de escritura
export function mergeComments(current: Comment[], incoming: Comment[]): Comment[] {
  const byId = new Map(current.map((comment) => [comment.id, comment]))
  incoming.forEach((comment) => byId.set(comment.id, comment))
  return [...byId.values()].sort((a, b) => (a.id < b.id ? -1 : a.id > b.id ? 1 : 0))
}

export function useConversation(incidentId: string) {
  const comments = ref<Comment[]>([])
  const total = ref(0)
  const before = ref<string | null>(null)
  const loaded = ref(false)
  const loading = ref(false)
  const sending = ref(false)
  const loadError = ref<ApiError | null>(null)
  const actionError = ref<ApiError | null>(null)

  // Lo más reciente: la primera vez reemplaza; después se une a lo que ya estaba (incluidos los anteriores cargados)
  async function refresh(): Promise<void> {
    loading.value = true
    loadError.value = null
    try {
      const page = await conversationApi.page(incidentId)
      comments.value = loaded.value ? mergeComments(comments.value, page.items) : mergeComments([], page.items)
      if (!loaded.value) before.value = page.before
      total.value = page.total
      loaded.value = true
    } catch (raw) {
      loadError.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  async function loadOlder(): Promise<void> {
    if (!before.value || loading.value) return
    loading.value = true
    try {
      const page = await conversationApi.page(incidentId, before.value)
      comments.value = mergeComments(comments.value, page.items)
      before.value = page.before
    } catch (raw) {
      actionError.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  async function send(body: string): Promise<boolean> {
    if (sending.value) return false
    sending.value = true
    actionError.value = null
    try {
      const comment = await conversationApi.write(incidentId, body)
      comments.value = mergeComments(comments.value, [comment])
      total.value += 1
      return true
    } catch (raw) {
      actionError.value = toApiError(raw)
      return false
    } finally {
      sending.value = false
    }
  }

  async function remove(commentId: string): Promise<boolean> {
    actionError.value = null
    try {
      const comment = await conversationApi.remove(incidentId, commentId)
      comments.value = mergeComments(comments.value, [comment])
      total.value = Math.max(0, total.value - 1)
      return true
    } catch (raw) {
      actionError.value = toApiError(raw)
      return false
    }
  }

  const clearError = () => {
    actionError.value = null
  }

  return {
    comments,
    total,
    before,
    loaded,
    loading,
    sending,
    loadError,
    actionError,
    refresh,
    loadOlder,
    send,
    remove,
    clearError,
  }
}
