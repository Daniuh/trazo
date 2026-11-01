<!-- Listado en tarjetas (celular y tablet): una por fila, con código, título, estado, prioridad, reapertura pendiente, asignado y vencimiento.
     Tocar una tarjeta abre el detalle. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { formatShortDateTime } from '@/shared/format/dates'
import AppButton from '@/shared/ui/AppButton.vue'
import type { IncidentSummary } from '../../domain/listing'
import IncidentBadges from '../components/IncidentBadges.vue'

defineProps<{ items: IncidentSummary[]; deleted: boolean }>()
const emit = defineEmits<{ open: [id: string]; restore: [id: string] }>()
</script>

<template>
  <ul class="incident-cards">
    <li v-for="incident in items" :key="incident.id">
      <article
        class="incident-cards__card"
        tabindex="0"
        :aria-label="`${incident.code}: ${incident.title}`"
        @click="emit('open', incident.id)"
        @keydown.enter="emit('open', incident.id)"
      >
        <p class="incident-cards__code">{{ incident.code }}</p>
        <h2 class="incident-cards__title">{{ incident.title }}</h2>
        <IncidentBadges :incident="incident" />
        <p v-if="incident.reopenRequested" class="incident-cards__reopen">Reapertura pendiente</p>
        <p class="incident-cards__meta">
          {{ incident.assignee?.name ?? 'Sin asignar' }} · vence {{ formatShortDateTime(incident.dueDate) }}
        </p>
        <AppButton
          v-if="deleted && incident.actions.restore"
          variant="secondary"
          icon="refresh"
          @click.stop="emit('restore', incident.id)"
        >
          Restaurar
        </AppButton>
      </article>
    </li>
  </ul>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-cards {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  margin: 0;
  padding: 0;
  list-style: none;
}

.incident-cards__card {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
  cursor: pointer;

  &:hover {
    border-color: $color-border-strong;
  }
}

.incident-cards__code,
.incident-cards__meta {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.incident-cards__reopen {
  margin: 0;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.incident-cards__code {
  font-weight: $font-weight-medium;
}

.incident-cards__title {
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
  overflow-wrap: anywhere;
}
</style>
