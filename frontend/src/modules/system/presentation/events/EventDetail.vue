<!-- Detalle de un evento: mensaje, severidad, cuántas veces y cuándo, estado (con quién lo cambió y los botones para
     pasarlo al siguiente), el contexto sin datos sensibles y el código de soporte para buscarlo en los logs.
     Documentado en docs/frontend/system.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatDateTime, formatRelative } from '@/shared/format/dates'
import { formatNumber } from '@/shared/format/numbers'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import { nextStatuses, type EventStatus, type SystemEvent } from '../../domain/system'
import {
  contextRows,
  EVENT_STATUS_LABEL,
  EVENT_STATUS_TONE,
  SEVERITY_LABEL,
  SEVERITY_TONE,
  STATUS_ACTION,
} from '../systemTexts'

const props = defineProps<{ event: SystemEvent; busy: boolean }>()
const emit = defineEmits<{ status: [status: EventStatus] }>()

const context = computed(() => contextRows(props.event.context))
</script>

<template>
  <div class="event-detail">
    <p class="event-detail__message">{{ event.message }}</p>

    <dl class="event-detail__facts">
      <div class="event-detail__fact">
        <dt>Severidad</dt>
        <dd>
          <AppBadge :tone="SEVERITY_TONE[event.type.severity]">{{ SEVERITY_LABEL[event.type.severity] }}</AppBadge>
        </dd>
      </div>
      <div class="event-detail__fact">
        <dt>Veces</dt>
        <dd>{{ formatNumber(event.occurrences) }}</dd>
      </div>
      <div class="event-detail__fact">
        <dt>Primera vez</dt>
        <dd>{{ formatDateTime(event.firstSeenAt) }}</dd>
      </div>
      <div class="event-detail__fact">
        <dt>Última vez</dt>
        <dd>
          {{ formatDateTime(event.lastSeenAt) }}
          <span class="event-detail__muted"> · {{ formatRelative(event.lastSeenAt) }}</span>
        </dd>
      </div>
      <div class="event-detail__fact">
        <dt>Estado</dt>
        <dd>
          <AppBadge :tone="EVENT_STATUS_TONE[event.status]">{{ EVENT_STATUS_LABEL[event.status] }}</AppBadge>
          <span v-if="event.statusChangedBy && event.statusChangedAt" class="event-detail__muted">
            · {{ event.statusChangedBy }}, {{ formatRelative(event.statusChangedAt) }}
          </span>
        </dd>
      </div>
    </dl>

    <div class="event-detail__actions">
      <AppButton
        v-for="(status, index) in nextStatuses(event.status)"
        :key="status"
        :variant="index === 0 ? 'primary' : 'secondary'"
        :disabled="busy"
        @click="emit('status', status)"
      >
        {{ STATUS_ACTION[status] }}
      </AppButton>
    </div>

    <section v-if="context.length > 0" class="event-detail__section" aria-label="Contexto">
      <h3 class="event-detail__heading">Contexto</h3>
      <dl class="event-detail__facts">
        <div v-for="row in context" :key="row.label" class="event-detail__fact">
          <dt>{{ row.label }}</dt>
          <dd>{{ row.value }}</dd>
        </div>
      </dl>
    </section>

    <p v-if="event.traceId" class="event-detail__muted">
      Código de soporte: <code>{{ event.traceId }}</code>
    </p>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.event-detail,
.event-detail__facts,
.event-detail__section {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  margin: 0;
}

.event-detail {
  gap: $space-5;
}

.event-detail__message {
  margin: 0;
  font-weight: $font-weight-medium;
  overflow-wrap: anywhere;
}

.event-detail__fact {
  display: flex;
  flex-direction: column;
  gap: $space-1;

  dt {
    color: $color-text-secondary;
    font-size: $font-size-xs;
    font-weight: $font-weight-medium;
  }

  dd {
    margin: 0;
    overflow-wrap: anywhere;
  }
}

.event-detail__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
}

.event-detail__heading {
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
}

.event-detail__muted {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
  overflow-wrap: anywhere;
}
</style>
