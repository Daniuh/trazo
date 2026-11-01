// Estado del centro de notificaciones (Pinia): las sin leer de cada pestaña para la campana y la lista de cada pestaña,
// que se pide al abrir el panel. Cuando llega un aviso de tiempo real se vuelven a pedir las sin leer y, si el panel
// está abierto, la lista que se ve. Documentado en docs/frontend/notifications.md
import { defineStore } from 'pinia'
import { computed, reactive, ref } from 'vue'
import { toApiError, type ApiError } from '@/shared/http'
import { noUnread, totalUnread, type Notification, type NotificationCategory } from '../domain/notification'
import { notificationsApi } from '../infrastructure/notificationsApi'

interface CategoryList {
  items: Notification[]
  before: string | null
  // Vigente: si llegó algo nuevo mientras el panel estaba cerrado, se vuelve a pedir al abrirlo
  loaded: boolean
  loading: boolean
  error: ApiError | null
}

const emptyList = (): CategoryList => ({ items: [], before: null, loaded: false, loading: false, error: null })

export const useNotificationsStore = defineStore('notifications', () => {
  const unread = ref(noUnread())
  const lists = reactive<Record<NotificationCategory, CategoryList>>({
    conversations: emptyList(),
    activity: emptyList(),
  })
  const panelOpen = ref(false)
  const actionError = ref<ApiError | null>(null)
  const owner = ref<string | null>(null)

  const total = computed(() => totalUnread(unread.value))

  // Con otra persona en la misma pestaña, nada de lo anterior sirve
  async function start(userId: string): Promise<void> {
    if (owner.value !== userId) {
      owner.value = userId
      unread.value = noUnread()
      lists.conversations = emptyList()
      lists.activity = emptyList()
    }
    await refreshUnread()
  }

  async function refreshUnread(): Promise<void> {
    try {
      unread.value = await notificationsApi.unread()
    } catch {
      // La campana se queda con el último número que tenía; la próxima consulta lo corrige
    }
  }

  async function load(category: NotificationCategory, more = false): Promise<void> {
    const list = lists[category]
    if (list.loading || (more && !list.before)) return
    list.loading = true
    list.error = null
    try {
      const page = await notificationsApi.page(category, more ? list.before : null)
      list.items = more ? [...list.items, ...page.items] : page.items
      list.before = page.before
      list.loaded = true
      unread.value = page.unread
    } catch (raw) {
      list.error = toApiError(raw)
    } finally {
      list.loading = false
    }
  }

  async function ensure(category: NotificationCategory): Promise<void> {
    if (!lists[category].loaded) await load(category)
  }

  function setRead(category: NotificationCategory, match: (item: Notification) => boolean): void {
    lists[category].items = lists[category].items.map((item) => (match(item) ? { ...item, read: true } : item))
  }

  // Al abrir un aviso queda leído; si falla, el aviso igual lleva a su incidente
  async function markRead(item: Notification): Promise<void> {
    if (item.read) return
    setRead(item.category, (other) => other.id === item.id)
    try {
      unread.value = await notificationsApi.markRead(item.id)
    } catch {
      await refreshUnread()
    }
  }

  async function markAllRead(category: NotificationCategory): Promise<void> {
    actionError.value = null
    try {
      unread.value = await notificationsApi.markAllRead(category)
      setRead(category, () => true)
    } catch (raw) {
      actionError.value = toApiError(raw)
    }
  }

  // Quien tiene abierta la conversación de un incidente ya leyó sus mensajes, también los que lleguen mientras tanto
  const openConversation = ref<string | null>(null)

  async function markConversationRead(): Promise<void> {
    const incidentId = openConversation.value
    if (!incidentId || unread.value.conversations === 0) return
    try {
      unread.value = await notificationsApi.markAllRead('conversations', incidentId)
      setRead('conversations', (item) => item.incident?.id === incidentId)
    } catch {
      // No impide leer la conversación; el aviso seguirá como sin leer
    }
  }

  async function watchConversation(incidentId: string | null): Promise<void> {
    openConversation.value = incidentId
    await markConversationRead()
  }

  // Llegó algo nuevo (o se leyó en otra pestaña)
  async function changed(): Promise<void> {
    lists.conversations.loaded = false
    lists.activity.loaded = false
    await refreshUnread()
    await markConversationRead()
  }

  return {
    unread,
    lists,
    panelOpen,
    actionError,
    total,
    start,
    refreshUnread,
    load,
    ensure,
    markRead,
    markAllRead,
    watchConversation,
    changed,
  }
})
