<!-- Panel del detalle con dos pestañas: Chat (los mensajes en orden de conversación, lo nuevo abajo, y el único lugar
     para escribir) y Actividad (todo el historial, lo nuevo arriba, con las notas completas). Se abre en la entrada
     que se tocó en el historial y la resalta. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { messageFor } from '@/shared/errors'
import AppButton from '@/shared/ui/AppButton.vue'
import AppSheet from '@/shared/ui/AppSheet.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'
import type { useConversation } from '../../application/useConversation'
import { activityFeed, type ActivityPanelTab } from '../../domain/activityFeed'
import type { Activity, Incident } from '../../domain/incident'
import ActivityEntry from './ActivityEntry.vue'
import ChatComposer from './ChatComposer.vue'
import ChatMessage from './ChatMessage.vue'

const props = defineProps<{
  incident: Incident
  activities: Activity[]
  activityTotal: number
  conversation: ReturnType<typeof useConversation>
  // El mensaje (id) o la entrada de la actividad (key) a la que se llega
  target: string | null
}>()
const emit = defineEmits<{ 'load-older': [] }>()
const open = defineModel<boolean>('open', { required: true })
const tab = defineModel<ActivityPanelTab>('tab', { required: true })

const { confirm } = useFeedback()
const { comments, total: messageTotal, before, loading, actionError } = props.conversation
const content = ref<HTMLElement | null>(null)
const composer = ref<InstanceType<typeof ChatComposer> | null>(null)
const highlighted = ref<string | null>(null)

const tabs = computed(() => [
  { label: `Chat (${messageTotal.value})`, value: 'chat' as const },
  { label: `Actividad (${props.activityTotal + messageTotal.value})`, value: 'activity' as const },
])
const feed = computed(() => activityFeed(props.activities, comments.value))
const hasOlderActivity = computed(() => props.activities.length < props.activityTotal || before.value !== null)

// Desde la actividad, un mensaje lleva al chat en ese mensaje
function showMessage(id: string): void {
  highlighted.value = id
  tab.value = 'chat'
}

async function remove(id: string): Promise<void> {
  const ok = await confirm({
    title: '¿Borrar este mensaje?',
    message: 'En el chat quedará "Mensaje eliminado" en su lugar.',
    confirmLabel: 'Borrar',
    danger: true,
  })
  if (ok) await props.conversation.remove(id)
}

// Lleva a la entrada pedida; en el chat, sin entrada pedida, al último mensaje
async function reveal(): Promise<void> {
  await nextTick()
  const box = content.value
  if (!box) return
  const wanted = highlighted.value ? box.querySelector(`[data-entry="${highlighted.value}"]`) : null
  const fallback = tab.value === 'chat' ? box.querySelector('[data-entry]:last-of-type') : null
  ;(wanted ?? fallback)?.scrollIntoView({ block: wanted ? 'center' : 'end' })
}

watch(
  () => [open.value, props.target] as const,
  ([isOpen, target]) => {
    if (isOpen) highlighted.value = target
  },
  { immediate: true },
)
watch([open, tab, highlighted], () => {
  if (open.value) void reveal()
})
// Un mensaje propio recién enviado se ve abajo
watch(
  () => comments.value.length,
  () => {
    if (open.value && tab.value === 'chat') void reveal()
  },
)

// Desde un aviso de mensajes: el campo se enfoca cuando el panel terminó de abrirse (antes, el diálogo se lleva el foco)
let focusWhenShown = false

function focusComposer(): void {
  focusWhenShown = true
}

function onShown(): void {
  void reveal()
  if (focusWhenShown) composer.value?.focus()
  focusWhenShown = false
}

defineExpose({ focusComposer })
</script>

<template>
  <AppSheet v-model:open="open" title="Chat y actividad" @shown="onShown">
    <template #toolbar>
      <AppSegmented v-model="tab" label="Ver" :options="tabs" />
    </template>

    <div ref="content" class="activity-panel">
      <template v-if="tab === 'chat'">
        <AppButton v-if="before" variant="link" :loading="loading" @click="conversation.loadOlder">
          Ver mensajes anteriores
        </AppButton>
        <p v-if="comments.length === 0" class="activity-panel__empty">
          Todavía no hay mensajes. Escribe el primero para coordinar con quienes atienden el incidente.
        </p>
        <!-- role="log" anuncia los mensajes nuevos; va afuera para que la lista conserve su semántica -->
        <div class="activity-panel__log" role="log" aria-label="Mensajes del incidente">
          <ol class="activity-panel__list">
            <ChatMessage
              v-for="comment in comments"
              :key="comment.id"
              :comment="comment"
              :highlighted="highlighted === comment.id"
              @remove="remove"
            />
          </ol>
        </div>
      </template>

      <template v-else>
        <ol class="activity-panel__list" aria-label="Toda la actividad">
          <ActivityEntry
            v-for="entry in feed"
            :key="entry.key"
            :entry="entry"
            full-note
            :highlighted="highlighted === entry.key"
            @message="showMessage"
          />
        </ol>
        <AppButton v-if="hasOlderActivity" variant="link" :loading="loading" @click="emit('load-older')">
          Ver actividad anterior
        </AppButton>
      </template>
    </div>

    <template v-if="tab === 'chat'" #footer>
      <AppAlert v-if="actionError">{{ messageFor(actionError) }}</AppAlert>
      <ChatComposer v-if="incident.actions.comment" ref="composer" :conversation="conversation" />
      <p v-else class="activity-panel__readonly">Puedes leer los mensajes, pero no escribir en este incidente.</p>
    </template>
  </AppSheet>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.activity-panel {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: $space-2;
}

.activity-panel__log {
  align-self: stretch;
}

.activity-panel__list {
  align-self: stretch;
  margin: 0;
  padding: 0;
  list-style: none;
}

.activity-panel__empty,
.activity-panel__readonly {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}
</style>
