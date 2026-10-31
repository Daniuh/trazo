<!-- Campos del incidente. Portátil y monitor: título y descripción a la izquierda; prioridad, vencimiento y
     asignación a la derecha. Celular y tablet: una columna. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { formatDateTime } from '@/shared/format/dates'
import AppCard from '@/shared/ui/AppCard.vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import type { SelectOption } from '@/shared/ui/form/types'
import { useAssignees } from '../../application/useAssignees'
import { useCatalogStore } from '../../application/useCatalogStore'
import { DESCRIPTION_MAX, TITLE_MAX } from '../../domain/incident'

const props = defineProps<{
  errors: Record<string, string>
  suggestedDueDate: string | null
  // Solo al crear y con permiso de asignar
  canAssign: boolean
}>()

const title = defineModel<string>('title', { required: true })
const description = defineModel<string>('description', { required: true })
const priority = defineModel<string>('priority', { required: true })
const dueDate = defineModel<string>('dueDate', { required: true })
const assignee = defineModel<string | null>('assignee', { required: true })

const catalogs = useCatalogStore()
const assignees = useAssignees()

const priorityOptions = computed<SelectOption<string>[]>(() =>
  catalogs.priorities.map((item) => ({ label: item.name, value: item.code })),
)
const assigneeOptions = computed<SelectOption<string>[]>(() =>
  assignees.people.value.map((person) => ({ label: person.name, value: person.id })),
)
const duePlaceholder = computed(() =>
  props.suggestedDueDate ? `Sugerida: ${formatDateTime(props.suggestedDueDate)}` : undefined,
)

onMounted(() => {
  if (props.canAssign) void assignees.load()
})
</script>

<template>
  <div class="incident-form">
    <AppCard class="incident-form__main">
      <div class="incident-form__fields">
        <AppTextField
          v-model="title"
          label="Título"
          placeholder="Resume el problema en una línea"
          :maxlength="TITLE_MAX"
          :error="errors.title"
          required
          autofocus
        />
        <AppTextField
          v-model="description"
          label="Descripción"
          type="textarea"
          placeholder="Qué pasa, desde cuándo y a quién afecta"
          :maxlength="DESCRIPTION_MAX"
          :error="errors.description"
          required
        />
      </div>
    </AppCard>

    <AppCard class="incident-form__side">
      <div class="incident-form__fields">
        <AppSelect v-model="priority" label="Prioridad" :options="priorityOptions" :error="errors.priority" required />
        <AppTextField
          v-model="dueDate"
          label="Vence"
          type="datetime-local"
          info="Si la dejas vacía, se calcula sola con el tiempo de resolución de la prioridad elegida."
          :error="errors.due_date"
        />
        <p v-if="!dueDate && duePlaceholder" class="incident-form__hint">{{ duePlaceholder }}</p>
        <AppSelect
          v-if="canAssign"
          v-model="assignee"
          label="Asignar a"
          :options="assigneeOptions"
          placeholder="Sin asignar"
          :error="errors.assignee"
        />
      </div>
    </AppCard>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-form,
.incident-form__fields {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.incident-form__hint {
  margin: -$space-2 0 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

@include laptop {
  .incident-form {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
    align-items: start;
  }
}
</style>
