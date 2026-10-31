<!-- Vista previa del listado (solo monitor): lo esencial del incidente elegido sin salir del listado, con
     "Cambiar estado" y "Abrir detalle". Reutiliza las piezas del detalle. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { onMounted } from 'vue'
import { messageFor } from '@/shared/errors'
import { ApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useIncidentDetail } from '../../application/useIncidentDetail'
import IncidentBadges from '../components/IncidentBadges.vue'
import IncidentFacts from '../components/IncidentFacts.vue'
import IncidentStatusButton from '../components/IncidentStatusButton.vue'
import { useStatusChange } from '../useStatusChange'

const props = defineProps<{ id: string }>()
const emit = defineEmits<{ changed: []; close: []; open: [id: string] }>()

const detail = useIncidentDetail(props.id)
const { incident, loadError, actionError, conflict, busy } = detail
const changeStatus = useStatusChange(incident, detail.changeStatus)
const conflictMessage = messageFor(new ApiError('http', 'VERSION_CONFLICT'))

async function onStatus(code: string): Promise<void> {
  if (await changeStatus(code)) emit('changed')
}

onMounted(detail.load)
</script>

<template>
  <aside class="incident-preview" aria-label="Vista previa del incidente">
    <button class="incident-preview__close" type="button" aria-label="Cerrar vista previa" @click="emit('close')">
      <AppIcon name="close" />
    </button>

    <AppErrorState v-if="loadError" :error="loadError" @retry="detail.load" />
    <AppSkeleton v-else-if="!incident" :lines="6" />

    <template v-else>
      <p class="incident-preview__code">{{ incident.code }}</p>
      <h2 class="incident-preview__title">{{ incident.title }}</h2>
      <IncidentBadges :incident="incident" />

      <AppAlert v-if="conflict">
        {{ conflictMessage }}
        <AppButton variant="link" icon="refresh" @click="detail.load">Recargar</AppButton>
      </AppAlert>
      <AppAlert v-else-if="actionError">{{ messageFor(actionError) }}</AppAlert>

      <p class="incident-preview__description">{{ incident.description }}</p>
      <IncidentFacts :incident="incident" />

      <div class="incident-preview__actions">
        <IncidentStatusButton
          v-if="incident.actions.changeStatus && incident.nextStatuses.length > 0"
          :next-statuses="incident.nextStatuses"
          :busy="busy"
          @status="onStatus"
        />
        <AppButton variant="secondary" @click="emit('open', incident.id)">Abrir detalle</AppButton>
      </div>
    </template>
  </aside>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-preview {
  position: sticky;
  top: $space-4;
  display: flex;
  flex-direction: column;
  gap: $space-3;
  padding: $space-5;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.incident-preview__close {
  position: absolute;
  top: $space-3;
  right: $space-3;
  display: inline-flex;
  padding: $space-1;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text-secondary;
  cursor: pointer;

  &:hover {
    background: $color-page;
    color: $color-text;
  }
}

.incident-preview__code {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.incident-preview__title {
  margin: 0;
  padding-right: $space-6;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
  overflow-wrap: anywhere;
}

// La descripción se resume en pocas líneas; el texto completo está en el detalle
.incident-preview__description {
  display: -webkit-box;
  margin: 0;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 5;
  line-clamp: 5;
  white-space: pre-line;
}

.incident-preview__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
}
</style>
