<!-- Página para crear o editar un incidente (un solo formulario). Al guardar, lleva al detalle.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { messageFor } from '@/shared/errors'
import { ApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useIncidentForm } from '../application/useIncidentForm'
import IncidentForm from './components/IncidentForm.vue'

const route = useRoute()
const router = useRouter()
const session = useSessionStore()
const { notify } = useFeedback()

const editingId = route.params.id ? String(route.params.id) : null
const { form, status, loadError, fieldErrors, formError, conflict, submitting, suggestedDueDate, prepare, submit } =
  useIncidentForm(editingId)
const conflictMessage = messageFor(new ApiError('http', 'VERSION_CONFLICT'))
const canAssign = computed(() => !editingId && session.can('incidents.assign'))

const toDetail = (id: string) => router.replace({ name: 'incident-detail', params: { id } })

// Si ya no se puede editar (otro lo cerró, por ejemplo), se vuelve al detalle
async function load(): Promise<void> {
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
    <h1 class="incident-form-page__title">{{ editingId ? 'Editar incidente' : 'Nuevo incidente' }}</h1>

    <AppErrorState v-if="loadError" :error="loadError" @retry="load" />
    <AppSkeleton v-else-if="status !== 'ready'" :lines="6" />

    <form v-else class="incident-form-page__form" novalidate @submit.prevent="save">
      <AppAlert v-if="conflict">
        {{ conflictMessage }}
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

// En celular los botones se apilan, con la acción principal arriba
.incident-form-page__actions {
  display: flex;
  flex-direction: column-reverse;
  gap: $space-2;
}

@include tablet {
  .incident-form-page__actions {
    flex-direction: row;
    justify-content: flex-end;
  }
}

@include laptop {
  .incident-form-page__title {
    font-size: $font-size-2xl;
  }
}
</style>
