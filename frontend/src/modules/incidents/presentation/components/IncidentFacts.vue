<!-- Tarjeta "Datos" de la vista previa del listado: quién lo reportó, quién lo atiende y sus fechas. -->
<!-- Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatDateTime, formatRelative } from '@/shared/format/dates'
import AppCard from '@/shared/ui/AppCard.vue'
import type { Incident } from '../../domain/incident'

// Estado y prioridad ya van bajo el título de la vista previa
const props = defineProps<{ incident: Incident }>()

const facts = computed(() => [
  { label: 'Reportó', value: props.incident.creator.name },
  { label: 'Asignado a', value: props.incident.assignee?.name ?? 'Sin asignar' },
  { label: 'Vence', value: formatDateTime(props.incident.dueDate) },
  ...(props.incident.resolvedAt ? [{ label: 'Resuelto', value: formatDateTime(props.incident.resolvedAt) }] : []),
  { label: 'Creado', value: formatDateTime(props.incident.createdAt) },
  { label: 'Actualizado', value: formatRelative(props.incident.updatedAt) },
])
</script>

<template>
  <AppCard title="Datos">
    <dl class="incident-facts">
      <div v-for="fact in facts" :key="fact.label" class="incident-facts__row">
        <dt class="incident-facts__label">{{ fact.label }}</dt>
        <dd class="incident-facts__value">{{ fact.value }}</dd>
      </div>
    </dl>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-facts {
  margin: 0;
}

.incident-facts__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
  padding: $space-2 0;
  border-bottom: 1px solid $color-border;

  &:last-child {
    border-bottom: 0;
  }
}

.incident-facts__label {
  color: $color-text-secondary;
}

.incident-facts__value {
  margin: 0;
  text-align: right;
}
</style>
