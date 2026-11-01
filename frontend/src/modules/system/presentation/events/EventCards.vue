<!-- Eventos en tarjetas (celular y tablet): severidad y estado, evento, mensaje, veces y última vez. Tocar una tarjeta
     abre su detalle. Documentado en docs/frontend/system.md -->
<script setup lang="ts">
import { formatShortDateTime } from '@/shared/format/dates'
import { formatNumber } from '@/shared/format/numbers'
import AppBadge from '@/shared/ui/AppBadge.vue'
import type { SystemEvent } from '../../domain/system'
import { EVENT_STATUS_LABEL, EVENT_STATUS_TONE, SEVERITY_LABEL, SEVERITY_TONE } from '../systemTexts'

defineProps<{ items: SystemEvent[] }>()
const emit = defineEmits<{ open: [id: number] }>()
</script>

<template>
  <ul class="event-cards">
    <li v-for="event in items" :key="event.id">
      <article
        class="event-cards__card"
        tabindex="0"
        :aria-label="`${event.type.name}: ${event.message}`"
        @click="emit('open', event.id)"
        @keydown.enter="emit('open', event.id)"
      >
        <div class="event-cards__badges">
          <AppBadge :tone="SEVERITY_TONE[event.type.severity]">{{ SEVERITY_LABEL[event.type.severity] }}</AppBadge>
          <AppBadge :tone="EVENT_STATUS_TONE[event.status]">{{ EVENT_STATUS_LABEL[event.status] }}</AppBadge>
        </div>
        <h2 class="event-cards__title">{{ event.type.name }}</h2>
        <p class="event-cards__message">{{ event.message }}</p>
        <p class="event-cards__meta">
          {{ formatNumber(event.occurrences) }} {{ event.occurrences === 1 ? 'vez' : 'veces' }} · última
          {{ formatShortDateTime(event.lastSeenAt) }}
        </p>
      </article>
    </li>
  </ul>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.event-cards {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  margin: 0;
  padding: 0;
  list-style: none;
}

.event-cards__card {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
  cursor: pointer;

  &:hover {
    border-color: $color-border-strong;
  }
}

.event-cards__badges {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
}

.event-cards__title,
.event-cards__message,
.event-cards__meta {
  margin: 0;
  overflow-wrap: anywhere;
}

.event-cards__title {
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.event-cards__message {
  font-size: $font-size-sm;
}

.event-cards__meta {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}
</style>
