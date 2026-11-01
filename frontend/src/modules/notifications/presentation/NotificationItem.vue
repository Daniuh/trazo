<!-- Un aviso del centro de notificaciones: qué pasó, en qué incidente, lo que se dijo y hace cuánto. Sin leer: punto azul
     y texto en negrita. Si el incidente ya no se puede ver, el aviso no lleva a ningún lado. Documentado en docs/frontend/notifications.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatDateTime, formatRelative } from '@/shared/format/dates'
import { detail, headline, type Notification } from '../domain/notification'

const props = defineProps<{ item: Notification }>()
defineEmits<{ open: [item: Notification] }>()

const text = computed(() => headline(props.item))
const said = computed(() => detail(props.item))
</script>

<template>
  <li class="notification" :class="{ 'notification--unread': !item.read }">
    <button v-if="item.incident" class="notification__body" type="button" @click="$emit('open', item)">
      <span class="notification__dot" aria-hidden="true" />
      <span class="notification__content">
        <span v-if="!item.read" class="notification__hidden">Sin leer.</span>
        <span class="notification__headline">{{ text }}</span>
        <span class="notification__incident">{{ item.incident.code }} · {{ item.incident.title }}</span>
        <span
          v-if="said"
          class="notification__detail"
          :class="{ 'notification__detail--deleted': item.commentDeleted && item.type === 'comment_received' }"
        >
          {{ said }}
        </span>
        <time class="notification__time" :datetime="item.createdAt" :title="formatDateTime(item.createdAt)">
          {{ formatRelative(item.createdAt) }}
        </time>
      </span>
    </button>
    <div v-else class="notification__body notification__body--gone">
      <span class="notification__dot" aria-hidden="true" />
      <span class="notification__content">
        <span class="notification__headline">{{ text }}</span>
        <span class="notification__incident">Ya no tienes acceso a este incidente</span>
        <time class="notification__time" :datetime="item.createdAt">{{ formatRelative(item.createdAt) }}</time>
      </span>
    </div>
  </li>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.notification + .notification {
  border-top: 1px solid $color-border;
}

.notification__body {
  display: flex;
  gap: $space-3;
  width: 100%;
  padding: $space-3 $space-2;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text;
  font: inherit;
  text-align: left;
  cursor: pointer;

  &:hover {
    background: $color-page;
  }
}

.notification__body--gone {
  cursor: default;

  &:hover {
    background: none;
  }
}

// El punto solo se pinta si está sin leer; ocupa su lugar siempre para que los textos queden alineados
.notification__dot {
  flex: none;
  width: $space-2;
  height: $space-2;
  margin-top: 0.4rem;
  border-radius: 50%;
}

.notification--unread .notification__dot {
  background: $color-primary;
}

.notification__content {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 0.125rem;
  min-width: 0;
}

.notification__hidden {
  @include visually-hidden;
}

.notification__headline {
  font-size: $font-size-sm;
}

.notification--unread .notification__headline {
  font-weight: $font-weight-semibold;
}

.notification__incident,
.notification__detail {
  overflow: hidden;
  color: $color-text-secondary;
  font-size: $font-size-sm;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.notification__detail--deleted {
  font-style: italic;
}

.notification__time {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}
</style>
