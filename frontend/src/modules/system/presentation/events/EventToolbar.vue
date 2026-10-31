<!-- Filtros de los eventos: qué se ve (pendientes, resueltos o todos), severidad y tipo. Caben en una fila desde
     tablet; en celular se apilan. Todo vive en la URL. Documentado en docs/frontend/system.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import type { EventCriteria, EventType, EventView, Severity } from '../../domain/system'
import { SEVERITY_LABEL } from '../systemTexts'

const props = defineProps<{ criteria: EventCriteria; types: EventType[] }>()
const emit = defineEmits<{ change: [changes: Partial<EventCriteria>] }>()

const VIEWS: { label: string; value: EventView }[] = [
  { label: 'Pendientes', value: 'pending' },
  { label: 'Resueltos', value: 'resolved' },
  { label: 'Todos', value: 'all' },
]
const severities = (Object.keys(SEVERITY_LABEL) as Severity[]).map((value) => ({ label: SEVERITY_LABEL[value], value }))
const typeOptions = computed(() => props.types.map((type) => ({ label: type.name, value: type.code })))

const view = computed({
  get: () => props.criteria.view,
  set: (value: EventView) => emit('change', { view: value }),
})
const severity = computed({
  get: () => props.criteria.severity,
  set: (value: Severity | Severity[] | null) => emit('change', { severity: Array.isArray(value) ? null : value }),
})
const type = computed({
  get: () => props.criteria.type,
  set: (value: string | string[] | null) => emit('change', { type: Array.isArray(value) ? null : value }),
})
</script>

<template>
  <div class="event-toolbar">
    <AppSegmented v-model="view" label="Qué eventos ver" :options="VIEWS" />
    <AppSelect
      v-model="severity"
      class="event-toolbar__field"
      label="Severidad"
      placeholder="Todas"
      :options="severities"
    />
    <AppSelect
      v-model="type"
      class="event-toolbar__field"
      label="Tipo"
      placeholder="Todos"
      filterable
      :options="typeOptions"
    />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.event-toolbar {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

@include tablet {
  .event-toolbar {
    flex-flow: row wrap;
    align-items: flex-end;
  }

  .event-toolbar__field {
    flex: 1;
    min-width: $filter-field-width;
  }
}
</style>
