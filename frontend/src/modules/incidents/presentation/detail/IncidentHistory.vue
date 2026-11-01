<!-- Historial del detalle: las 5 entradas más recientes, mensajes y cambios juntos, lo nuevo arriba. Un mensaje abre
     el chat en ese mensaje; una nota larga abre la actividad con la nota completa; "Ver toda la actividad" abre el
     panel. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import { activityFeed, RECENT_ACTIVITY } from '../../domain/activityFeed'
import type { Activity, Comment } from '../../domain/incident'
import ActivityEntry from './ActivityEntry.vue'

const props = defineProps<{ activities: Activity[]; comments: Comment[]; total: number }>()
const emit = defineEmits<{ message: [id: string]; note: [key: string]; all: [] }>()

const recent = computed(() => activityFeed(props.activities, props.comments).slice(0, RECENT_ACTIVITY))
</script>

<template>
  <AppCard title="Historial" class="incident-history">
    <ol class="incident-history__list" aria-label="Actividad reciente">
      <ActivityEntry
        v-for="entry in recent"
        :key="entry.key"
        :entry="entry"
        @message="emit('message', $event)"
        @note="emit('note', $event)"
      />
    </ol>
    <AppButton v-if="total > 0" class="incident-history__all" variant="secondary" @click="emit('all')">
      Ver toda la actividad ({{ total }})
    </AppButton>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-history__list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.incident-history__all {
  margin-top: $space-2;
}
</style>
