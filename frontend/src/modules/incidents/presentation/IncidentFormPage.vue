<!-- Página para crear o editar un incidente (un solo formulario). Al guardar, lleva al detalle.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { useRealtime } from '@/modules/realtime/application/realtimeBus'
import { messageFor } from '@/shared/errors'
import { ApiError } from '@/shared/http'
import AppBackLink from '@/shared/ui/AppBackLink.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { listReturnLocation } from '../application/listReturn'
import { useIncidentForm } from '../application/useIncidentForm'
import IncidentForm from './components/IncidentForm.vue'

const route = useRoute()
const router = useRouter()
const session = useSessionStore()
const { notify } = useFeedback()

const editingId = route.params.id ? String(route.params.id) : null
const {
  form,
  existing,
  status,
  loadError,
  fieldErrors,
  formError,
  conflict,
  submitting,
  suggestedDueDate,
  prepare,
  submit,
} = useIncidentForm(editingId)
// Al editar, la vista previa muestra el código, el estado y la persona asignada de verdad
const current = computed(() =>
  existing.value
    ? { code: existing.value.code, status: existing.value.status, assigneeName: existing.value.assignee?.name ?? null }
    : null,
)
const conflictMessage = messageFor(new ApiError('http', 'VERSION_CONFLICT'))
const canAssign = computed(() => !editingId && session.can('incidents.assign'))
// Quien solo toma incidentes puede asignarse el que crea (el back solo le permite elegirse a sí mismo)
const selfAssignId = computed(() =>
  !editingId && !canAssign.value && session.can('incidents.take') ? (session.user?.id ?? null) : null,
)

const toDetail = (id: string) => router.replace({ name: 'incident-detail', params: { id } })

// Otra persona guardó cambios mientras se editaba: se avisa, sin tocar lo que ya se escribió
const changedElsewhere = ref(false)
useRealtime('incident', (change) => {
  if (editingId && change.id === editingId) changedElsewhere.value = true
})

// Si ya no se puede editar (otro lo cerró, por ejemplo), se vuelve al detalle
async function load(): Promise<void> {
  changedElsewhere.value = false
  if ((await prepare()) === 'not-editable' && editingId) await toDetail(editingId)
}

async function save(): Promise<void> {
  const saved = await submit()
  if (!saved) return
  notify(editingId ? 'Cambios guardados.' : `Incidente ${saved.code} creado.`)
  await toDetail(saved.id)
}

function cancel(): void {
  if (editingId) void toDetail(editingId)
  else router.back()
}

onMounted(load)
</script>

<template>
  <div class="incident-form-page">
    <!-- Al editar se vuelve al incidente; al crear, al listado -->
    <AppBackLink v-if="editingId" :to="{ name: 'incident-detail', params: { id: editingId } }" label="Incidente" />
    <AppBackLink v-else :to="listReturnLocation()" label="Incidentes" />

    <h1 class="incident-form-page__title">{{ editingId ? 'Editar incidente' : 'Nuevo incidente' }}</h1>

    <AppErrorState v-if="loadError" :error="loadError" @retry="load" />
    <AppSkeleton v-else-if="status !== 'ready'" :lines="6" />

    <form v-else class="incident-form-page__form" novalidate @submit.prevent="save">
      <AppAlert v-if="conflict">
        {{ conflictMessage }}
        <AppButton variant="link" icon="refresh" @click="load">Recargar</AppButton>
      </AppAlert>
      <AppAlert v-else-if="changedElsewhere" tone="info">
        Otra persona cambió este incidente. Recarga para ver la versión actual antes de guardar; lo que escribiste se
        reemplaza.
        <AppButton variant="link" icon="refresh" @click="load">Recargar</AppButton>
      </AppAlert>
      <AppAlert v-else-if="formError">{{ formError }}</AppAlert>

      <IncidentForm
        v-model:title="form.title"
        v-model:description="form.description"
        v-model:priority="form.priority"
        v-model:due-date="form.dueDate"
        v-model:assignee="form.assignee"
        :errors="fieldErrors"
        :suggested-due-date="suggestedDueDate"
        :can-assign="canAssign"
        :self-assign-id="selfAssignId"
        :creating="!editingId"
        :current="current"
      />

      <div class="incident-form-page__actions">
        <AppButton variant="secondary" @click="cancel">Cancelar</AppButton>
        <AppButton type="submit" :loading="submitting">{{
          editingId ? 'Guardar cambios' : 'Crear incidente'
        }}</AppButton>
      </div>
    </form>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-form-page,
.incident-form-page__form {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.incident-form-page__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

// Celular y tablet: los botones quedan fijos abajo, sobre el dock, para no tener que bajar hasta el final.
// La acción principal ocupa más
.incident-form-page__actions {
  position: sticky;
  z-index: 1;
  bottom: $dock-clearance-mobile;
  display: flex;
  gap: $space-2;
  padding: $space-2;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
  box-shadow: $shadow-overlay;

  > :first-child {
    flex: 1;
  }

  > :last-child {
    flex: 2;
  }
}

@include tablet {
  .incident-form-page__actions {
    bottom: $dock-clearance;
  }
}

// Desde portátil, a la derecha y sin marco: el formulario cabe en la pantalla
@include laptop {
  .incident-form-page__actions {
    position: static;
    justify-content: flex-end;
    padding: 0;
    border: 0;
    background: none;
    box-shadow: none;

    > :first-child,
    > :last-child {
      flex: none;
    }
  }
}

@include laptop {
  .incident-form-page__title {
    font-size: $font-size-2xl;
  }
}
</style>
