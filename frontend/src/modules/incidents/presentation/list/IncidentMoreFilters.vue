<!-- Filtros adicionales: rangos de creación y vencimiento, solo vencidos, creados por mí y eliminados.
     En portátil van en el menú "Más filtros"; en celular y tablet, dentro del panel de filtros. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppCheckbox from '@/shared/ui/form/AppCheckbox.vue'
import AppDateRangeField from '@/shared/ui/form/AppDateRangeField.vue'
import type { ListCriteria } from '../../domain/listing'

const props = defineProps<{ criteria: ListCriteria; myId: string | null; canSeeDeleted: boolean }>()
const emit = defineEmits<{ change: [changes: Partial<ListCriteria>] }>()

const bind = <K extends keyof ListCriteria>(key: K) =>
  computed({
    get: () => props.criteria[key],
    set: (value: ListCriteria[K]) => emit('change', { [key]: value }),
  })

const createdFrom = bind('createdFrom')
const createdTo = bind('createdTo')
const dueFrom = bind('dueFrom')
const dueTo = bind('dueTo')
const overdue = bind('overdue')
const deleted = bind('deleted')
const mine = computed({
  get: () => props.criteria.creator !== null && props.criteria.creator === props.myId,
  set: (value: boolean) => emit('change', { creator: value ? props.myId : null }),
})
</script>

<template>
  <div class="more-filters">
    <AppDateRangeField
      v-model:from="createdFrom"
      v-model:to="createdTo"
      label="Creado entre"
      placeholder="Cualquier fecha"
    />
    <AppDateRangeField v-model:from="dueFrom" v-model:to="dueTo" label="Vence entre" placeholder="Cualquier fecha" />
    <div class="more-filters__checks">
      <AppCheckbox v-model="overdue" label="Solo vencidos" />
      <AppCheckbox v-if="myId" v-model="mine" label="Creados por mí" />
      <AppCheckbox v-if="canSeeDeleted" v-model="deleted" label="Ver eliminados" />
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.more-filters,
.more-filters__checks {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

.more-filters__checks {
  gap: $space-1;
}
</style>
