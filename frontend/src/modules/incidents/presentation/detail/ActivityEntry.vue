<!-- Una entrada de la actividad: un mensaje (botón que abre el chat en ese mensaje) o un cambio del historial con su
     nota. La usan el historial del detalle y la pestaña Actividad del panel (con la nota completa).
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatRelative } from '@/shared/format/dates'
import AppAvatar from '@/shared/ui/AppAvatar.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import type { FeedEntry } from '../../domain/activityFeed'
import HistoryNote from '../components/HistoryNote.vue'
import { describeActivity } from '../historyText'

const props = defineProps<{ entry: FeedEntry; fullNote?: boolean; highlighted?: boolean }>()
const emit = defineEmits<{ message: [id: string]; note: [key: string] }>()

const change = computed(() => (props.entry.kind === 'change' ? describeActivity(props.entry.activity) : null))
</script>

<template>
  <li class="activity-entry" :class="{ 'activity-entry--highlighted': highlighted }" :data-entry="entry.key">
    <!-- El nombre del botón es lo que se ve, más "Abrir en el chat" para quien no ve el ícono -->
    <button
      v-if="entry.kind === 'message'"
      type="button"
      class="activity-entry__message"
      @click="emit('message', entry.comment.id)"
    >
      <AppAvatar :name="entry.comment.author.name" size="md" />
      <span class="activity-entry__body">
        <span class="activity-entry__who">
          <strong>{{ entry.comment.mine ? 'Tú' : entry.comment.author.name }}</strong> escribió
          <time :datetime="entry.at">· {{ formatRelative(entry.at) }}</time>
        </span>
        <span v-if="entry.comment.body !== null" class="activity-entry__excerpt">{{ entry.comment.body }}</span>
        <span v-else class="activity-entry__excerpt activity-entry__excerpt--deleted">Mensaje eliminado</span>
      </span>
      <AppIcon class="activity-entry__chat" name="chat" />
      <span class="activity-entry__hint">. Abrir en el chat</span>
    </button>

    <template v-else-if="change">
      <span class="activity-entry__mark" aria-hidden="true" />
      <div class="activity-entry__body">
        <p class="activity-entry__change">
          {{ change.text }}
          <time class="activity-entry__time" :datetime="entry.at">· {{ formatRelative(entry.at) }}</time>
        </p>
        <p v-if="change.detail" class="activity-entry__detail">{{ change.detail }}</p>
        <HistoryNote
          v-if="entry.activity.note"
          :note="entry.activity.note"
          :full="fullNote"
          @more="emit('note', entry.key)"
        />
      </div>
    </template>
  </li>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.activity-entry {
  display: flex;
  gap: $space-3;
  padding: $space-3 0;
  border-top: 1px solid $color-border;
  scroll-margin: $space-4;

  &:first-child {
    border-top: none;
  }
}

// La entrada a la que se llegó desde el detalle: una franja suave que se nota sin gritar
.activity-entry--highlighted {
  margin: 0 (-$space-2);
  padding-right: $space-2;
  padding-left: $space-2;
  border-radius: $radius-control;
  background: $color-page;
}

// Todo el mensaje es el botón: se toca en cualquier parte. Relativo para que el texto oculto no se escape del panel
.activity-entry__message {
  position: relative;
  display: flex;
  flex: 1;
  align-items: flex-start;
  gap: $space-3;
  min-width: 0;
  padding: 0;
  border: none;
  border-radius: $radius-control;
  background: none;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;

  &:hover .activity-entry__excerpt {
    border-color: $color-border-strong;
  }
}

.activity-entry__body {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: flex-start;
  gap: $space-1;
  min-width: 0;
}

.activity-entry__who {
  font-size: $font-size-sm;

  time {
    color: $color-text-secondary;
    font-size: $font-size-xs;
  }
}

// El mensaje en dos líneas como máximo; completo, en el chat
.activity-entry__excerpt {
  display: -webkit-box;
  max-width: 100%;
  padding: $space-2 $space-3;
  overflow: hidden;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-page;
  font-size: $font-size-sm;
  white-space: pre-line;
  overflow-wrap: anywhere;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
}

.activity-entry__excerpt--deleted {
  background: transparent;
  color: $color-text-secondary;
  font-style: italic;
}

.activity-entry__chat {
  flex: none;
  align-self: center;
  color: $color-text-secondary;
}

.activity-entry__hint {
  @include visually-hidden;
}

// Un cambio: un punto en el lugar del círculo de la persona, y el texto discreto
.activity-entry__mark {
  display: flex;
  flex: none;
  justify-content: center;
  width: $avatar-size;
  padding-top: $space-1;

  &::before {
    width: $space-2;
    height: $space-2;
    border-radius: 50%;
    background: $color-border-strong;
    content: '';
  }
}

.activity-entry__change {
  margin: 0;
  font-size: $font-size-sm;
  overflow-wrap: anywhere;
}

.activity-entry__time,
.activity-entry__detail {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}
</style>
