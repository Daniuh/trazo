<!-- Panel del centro de notificaciones: desde la derecha en portátil y monitor, desde abajo en celular y tablet. Dos
     pestañas (Conversaciones y Actividad) con sus sin leer, la lista de la más nueva a la más vieja y "Marcar todo como
     leído". Abrir un aviso lo marca como leído y lleva a su incidente. Documentado en docs/frontend/notifications.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useRealtime } from '@/modules/realtime/application/realtimeBus'
import { messageFor } from '@/shared/errors'
import AppButton from '@/shared/ui/AppButton.vue'
import AppSheet from '@/shared/ui/AppSheet.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useNotificationsStore } from '../application/useNotificationsStore'
import { CATEGORIES, target, type Notification, type NotificationCategory } from '../domain/notification'
import NotificationItem from './NotificationItem.vue'

const open = defineModel<boolean>('open', { default: false })
const store = useNotificationsStore()
const router = useRouter()

const EMPTY: Record<NotificationCategory, { title: string; description: string }> = {
  conversations: {
    title: 'No hay mensajes',
    description: 'Aquí llegan las respuestas en las conversaciones de los incidentes en los que participas.',
  },
  activity: {
    title: 'No hay novedades',
    description: 'Aquí llega lo que pasa con tus incidentes: asignaciones, cambios de estado y reaperturas.',
  },
}

// Abre en la pestaña que tiene algo sin leer; si las dos tienen, en Conversaciones
const category = ref<NotificationCategory>('conversations')
const tabs = computed(() =>
  CATEGORIES.map((tab) => ({
    value: tab.value,
    label: store.unread[tab.value] > 0 ? `${tab.label} (${store.unread[tab.value]})` : tab.label,
  })),
)
const list = computed(() => store.lists[category.value])
const actionMessage = computed(() => (store.actionError ? messageFor(store.actionError) : ''))

watch(open, (isOpen) => {
  store.panelOpen = isOpen
  if (!isOpen) return
  if (store.unread.conversations === 0 && store.unread.activity > 0) category.value = 'activity'
  void store.ensure(category.value)
})
watch(category, (next) => void store.ensure(next))

// Con el panel abierto, lo nuevo aparece sin tener que cerrarlo
useRealtime('notifications', async () => {
  await store.changed()
  if (open.value) await store.ensure(category.value)
})

async function openItem(item: Notification): Promise<void> {
  const location = target(item)
  void store.markRead(item)
  open.value = false
  if (location) await router.push(location)
}
</script>

<template>
  <AppSheet v-model:open="open" title="Notificaciones">
    <AppSegmented v-model="category" label="Tipo de notificación" :options="tabs" class="center__tabs" />

    <AppAlert v-if="actionMessage" tone="danger">{{ actionMessage }}</AppAlert>

    <div v-if="list.loading && !list.loaded" aria-busy="true">
      <AppSkeleton :lines="6" />
    </div>
    <AppErrorState v-else-if="list.error && !list.loaded" :error="list.error" @retry="store.load(category)" />
    <AppEmptyState v-else-if="list.items.length === 0" v-bind="EMPTY[category]" />
    <template v-else>
      <ul class="center__list" :aria-label="CATEGORIES.find((tab) => tab.value === category)?.label">
        <NotificationItem v-for="item in list.items" :key="item.id" :item="item" @open="openItem" />
      </ul>
      <AppButton
        v-if="list.before"
        variant="link"
        class="center__more"
        :loading="list.loading"
        @click="store.load(category, true)"
      >
        Ver anteriores
      </AppButton>
    </template>

    <template v-if="store.unread[category] > 0" #footer>
      <AppButton variant="secondary" icon="check" block @click="store.markAllRead(category)">
        Marcar todo como leído
      </AppButton>
    </template>
  </AppSheet>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.center__tabs {
  align-self: flex-start;
}

.center__list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.center__more {
  align-self: center;
}
</style>
