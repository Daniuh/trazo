<!-- Acciones del encabezado del detalle: el siguiente paso como botón principal ("Pasar a En progreso") o, si el incidente
     terminó, "Reabrir" / "Solicitar reapertura"; y el menú ⋯ con los demás estados permitidos ("Cambiar a…").
     "Tomar" y "Asignar" van en la ficha. Todo sale de lo que la API permite. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppActionMenu from '@/shared/ui/AppActionMenu.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import type { MenuItem } from '@/shared/ui/menu'
import type { Incident, StatusOption } from '../../domain/incident'
import { nextStep } from '../../domain/workflow'

const props = defineProps<{ incident: Incident; catalog: StatusOption[]; busy?: boolean }>()
const emit = defineEmits<{ status: [code: string]; reopen: []; 'request-reopen': [] }>()

const allowed = computed(() => (props.incident.actions.changeStatus ? props.incident.nextStatuses : []))
const step = computed(() => nextStep(props.catalog, props.incident.status.code, allowed.value))

// Quien puede aprobar reabre directamente; el resto lo pide
const reopen = computed(() => {
  const can = props.incident.actions
  if (can.reopen) return { event: 'reopen' as const, label: 'Reabrir' }
  if (can.requestReopen) return { event: 'request-reopen' as const, label: 'Solicitar reapertura' }
  return null
})

const menu = computed<MenuItem[]>(() =>
  allowed.value
    .filter((status) => status.code !== step.value?.code)
    .map((status) => ({ key: status.code, label: `Cambiar a ${status.name}`, icon: 'refresh' })),
)

function triggerReopen(): void {
  if (reopen.value?.event === 'reopen') emit('reopen')
  else emit('request-reopen')
}
</script>

<template>
  <div class="detail-actions">
    <AppButton
      v-if="step"
      class="detail-actions__primary"
      icon="refresh"
      :loading="busy"
      @click="emit('status', step.code)"
    >
      Pasar a {{ step.name }}
    </AppButton>
    <AppButton v-else-if="reopen" class="detail-actions__primary" icon="refresh" :loading="busy" @click="triggerReopen">
      {{ reopen.label }}
    </AppButton>

    <AppButton v-if="menu.length > 0" variant="secondary" :disabled="busy" aria-label="Más acciones">
      <AppIcon name="more" />
      <AppActionMenu :items="menu" label="Más acciones" align="right" @select="emit('status', $event)" />
    </AppButton>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.detail-actions {
  display: flex;
  gap: $space-2;
}

// En celular el siguiente paso ocupa el ancho disponible
.detail-actions__primary {
  flex: 1;
}

@include tablet {
  .detail-actions__primary {
    flex: none;
  }
}
</style>
