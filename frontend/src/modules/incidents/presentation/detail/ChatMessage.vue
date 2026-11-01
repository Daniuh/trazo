<!-- Un mensaje del chat del incidente: quién, cuándo y el texto. Los propios van a la derecha en azul, y mientras se
     pueden borrar (5 minutos) muestran "Borrar". Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { formatRelative } from '@/shared/format/dates'
import AppAvatar from '@/shared/ui/AppAvatar.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import type { Comment } from '../../domain/incident'

defineProps<{ comment: Comment; highlighted?: boolean }>()
const emit = defineEmits<{ remove: [id: string] }>()
</script>

<template>
  <li
    class="chat-message"
    :class="{ 'chat-message--mine': comment.mine, 'chat-message--highlighted': highlighted }"
    :data-entry="comment.id"
  >
    <AppAvatar v-if="!comment.mine" :name="comment.author.name" size="md" />
    <div class="chat-message__body">
      <p class="chat-message__who">
        <strong>{{ comment.mine ? 'Tú' : comment.author.name }}</strong>
        <time :datetime="comment.createdAt">{{ formatRelative(comment.createdAt) }}</time>
      </p>
      <p v-if="comment.body !== null" class="chat-message__bubble">{{ comment.body }}</p>
      <p v-else class="chat-message__bubble chat-message__bubble--deleted">Mensaje eliminado</p>
      <AppButton v-if="comment.canDelete" variant="link" @click="emit('remove', comment.id)">Borrar</AppButton>
    </div>
  </li>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.chat-message {
  display: flex;
  gap: $space-2;
  padding: $space-2 0;
  scroll-margin: $space-4;
}

.chat-message--mine {
  justify-content: flex-end;
}

.chat-message__body {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: $space-1;
  min-width: 0;
  max-width: 85%;
}

.chat-message--mine .chat-message__body {
  align-items: flex-end;
}

.chat-message__who {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: $space-2;
  margin: 0;
  font-size: $font-size-sm;

  time {
    color: $color-text-secondary;
    font-size: $font-size-xs;
  }
}

.chat-message__bubble {
  max-width: 100%;
  margin: 0;
  padding: $space-2 $space-3;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-page;
  white-space: pre-line;
  overflow-wrap: anywhere;
}

// Los propios: azul de los botones con texto blanco (5:1)
.chat-message--mine .chat-message__bubble {
  border-color: $color-primary;
  background: $color-primary;
  color: $color-on-dark;
}

.chat-message__bubble--deleted,
.chat-message--mine .chat-message__bubble--deleted {
  border-color: $color-border;
  background: transparent;
  color: $color-text-secondary;
  font-style: italic;
}

// El mensaje al que se llegó desde el historial: un contorno que se nota sin cambiar sus colores
.chat-message--highlighted .chat-message__bubble {
  outline: 2px solid $color-focus;
  outline-offset: 2px;
}
</style>
