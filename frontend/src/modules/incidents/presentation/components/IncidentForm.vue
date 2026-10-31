<!-- Campos del incidente, con el formulario que guía (ADR-0082): título (y al crear, "¿Ya lo reportaron?"), descripción,
     prioridad en tarjetas con su plazo y vencimiento con atajos. Portátil y monitor: a la derecha, "Así se verá" y la
     asignación. Celular y tablet: una columna, con la asignación y luego la vista previa plegada.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, onMounted, toRef } from 'vue'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import AppCard from '@/shared/ui/AppCard.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppCheckbox from '@/shared/ui/form/AppCheckbox.vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import type { SelectOption } from '@/shared/ui/form/types'
import { useViewport } from '@/shared/ui/useViewport'
import { useAssignees } from '../../application/useAssignees'
import { useCatalogStore } from '../../application/useCatalogStore'
import { useSimilarIncidents } from '../../application/useSimilarIncidents'
import { DESCRIPTION_MAX, TITLE_MAX, type CatalogItem } from '../../domain/incident'
import { formatResolution } from '../../domain/reporting'
import DuePicker from '../form/DuePicker.vue'
import IncidentFormPreview from '../form/IncidentFormPreview.vue'
import PriorityPicker from '../form/PriorityPicker.vue'
import SimilarIncidents from '../form/SimilarIncidents.vue'

const props = defineProps<{
  errors: Record<string, string>
  suggestedDueDate: string | null
  // Al crear, el vencimiento se puede dejar vacío para que se calcule solo
  creating: boolean
  // Solo al crear y con permiso de asignar
  canAssign: boolean
  // Solo al crear, para quien puede tomar incidentes pero no asignarlos a otros: su propio id
  selfAssignId: string | null
  // Al editar: el estado y el código actuales, para la vista previa
  current?: { code: string; status: CatalogItem; assigneeName: string | null } | null
}>()

const title = defineModel<string>('title', { required: true })
const description = defineModel<string>('description', { required: true })
const priority = defineModel<string>('priority', { required: true })
const dueDate = defineModel<string | null>('dueDate', { required: true })
const assignee = defineModel<string | null>('assignee', { required: true })

const catalogs = useCatalogStore()
const assignees = useAssignees()
const session = useSessionStore()
const { isLaptopUp } = useViewport()
const similar = useSimilarIncidents(toRef(title), props.creating)

const assigneeOptions = computed<SelectOption<string>[]>(() =>
  assignees.people.value.map((person) => ({ label: person.name, value: person.id })),
)
// La casilla equivale a elegirse a sí mismo; desmarcada, queda sin asignar
const assignToSelf = computed({
  get: () => props.selfAssignId !== null && assignee.value === props.selfAssignId,
  set: (checked: boolean) => (assignee.value = checked ? props.selfAssignId : null),
})
const selectedPriority = computed(() => catalogs.priority(priority.value))
const resolution = computed(() =>
  selectedPriority.value ? formatResolution(selectedPriority.value.resolutionMinutes) : null,
)

// Vista previa: el estado con el que nace (o el actual al editar) y a quién queda asignado
const previewStatus = computed<CatalogItem>(() => {
  if (props.current) return props.current.status
  const initial = catalogs.catalogs?.statuses.find((status) => status.isInitial)
  return { code: initial?.code ?? 'open', name: initial?.name ?? '' }
})
const previewAssignee = computed(() => {
  if (props.current) return props.current.assigneeName
  if (assignee.value === null) return null
  if (assignee.value === props.selfAssignId) return session.user?.name ?? null
  return assignees.people.value.find((person) => person.id === assignee.value)?.name ?? null
})
const hasAssignment = computed(() => props.canAssign || props.selfAssignId !== null)

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
          counter
          :error="errors.title"
          required
          autofocus
        />
        <SimilarIncidents v-if="creating" :items="similar.items.value" :total="similar.total.value" />
        <AppTextField
          v-model="description"
          label="Descripción"
          type="textarea"
          placeholder="Qué pasa, desde cuándo y a quién afecta"
          :maxlength="DESCRIPTION_MAX"
          :error="errors.description"
          required
        />
        <PriorityPicker v-model="priority" :options="catalogs.priorities" :error="errors.priority" />
        <DuePicker
          v-model="dueDate"
          :creating="creating"
          :suggestion="suggestedDueDate"
          :resolution="resolution"
          :error="errors.due_date"
        />
      </div>
    </AppCard>

    <div class="incident-form__side">
      <AppCard v-if="isLaptopUp" class="incident-form__preview">
        <IncidentFormPreview
          :title="title"
          :priority="selectedPriority"
          :due-date="dueDate ?? suggestedDueDate"
          :status="previewStatus"
          :assignee-name="previewAssignee"
          :code="current?.code ?? null"
        />
      </AppCard>

      <AppCard v-if="hasAssignment" class="incident-form__assign">
        <AppSelect
          v-if="canAssign"
          v-model="assignee"
          label="Asignar a"
          :options="assigneeOptions"
          placeholder="Sin asignar"
          :error="errors.assignee"
        />
        <div v-else class="incident-form__self">
          <AppCheckbox v-model="assignToSelf" label="Asignármelo" />
          <AppInfoTip
            label="Sobre asignarte el incidente"
            text="Si la marcas, el incidente queda a tu nombre desde el inicio. Si no, queda sin asignar y cualquiera del equipo puede tomarlo."
          />
        </div>
        <p v-if="selfAssignId && errors.assignee" class="incident-form__error" role="alert">{{ errors.assignee }}</p>
      </AppCard>

      <!-- Celular y tablet: la vista previa plegada, para no alargar el formulario -->
      <details v-if="!isLaptopUp" class="incident-form__fold">
        <summary class="incident-form__summary">Así se verá</summary>
        <IncidentFormPreview
          class="incident-form__folded-preview"
          :title="title"
          :priority="selectedPriority"
          :due-date="dueDate ?? suggestedDueDate"
          :status="previewStatus"
          :assignee-name="previewAssignee"
          :code="current?.code ?? null"
        />
      </details>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-form,
.incident-form__fields,
.incident-form__side {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.incident-form__self {
  display: flex;
  align-items: center;
  gap: $space-2;
}

.incident-form__error {
  margin: 0;
  color: $color-danger;
  font-size: $font-size-sm;
}

.incident-form__fold {
  padding: $space-3 $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.incident-form__summary {
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
  cursor: pointer;
}

// Dentro del plegable el título ya está en el resumen
.incident-form__folded-preview {
  margin-top: $space-3;

  :deep(.form-preview__title) {
    @include visually-hidden;
  }
}

// Desde portátil, el formulario a la izquierda y la vista previa y la asignación a la derecha
@include laptop {
  .incident-form {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
    align-items: start;
  }
}
</style>
