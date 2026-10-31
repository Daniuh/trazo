<!-- Acciones del detalle, según lo que la API permite ("actions"). Portátil y monitor: botones en línea.
     Celular y tablet: "Cambiar estado" como botón principal y el resto en el menú ⋯. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppActionMenu from '@/shared/ui/AppActionMenu.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import type { MenuItem } from '@/shared/ui/menu'
import { useViewport } from '@/shared/ui/useViewport'
import type { Incident } from '../../domain/incident'
import IncidentStatusButton from './IncidentStatusButton.vue'

type ActionKey = 'take' | 'assign' | 'edit' | 'remove'

const props = defineProps<{ incident: Incident; busy?: boolean }>()
const emit = defineEmits<{ status: [code: string]; take: []; assign: []; edit: []; remove: [] }>()

const { isLaptopUp } = useViewport()

const secondary = computed<(MenuItem & { key: ActionKey })[]>(() => {
  const can = props.incident.actions
  const items: ((MenuItem & { key: ActionKey }) | false)[] = [
    can.take && { key: 'take', label: 'Tomar', icon: 'user' },
    can.assign && { key: 'assign', label: props.incident.assignee ? 'Reasignar' : 'Asignar', icon: 'users' },
    can.update && { key: 'edit', label: 'Editar', icon: 'edit' },
    can.delete && { key: 'remove', label: 'Eliminar', icon: 'trash', danger: true },
  ]
  return items.filter((item) => item !== false)
})

const canChangeStatus = computed(() => props.incident.actions.changeStatus && props.incident.nextStatuses.length > 0)

function trigger(key: string): void {
  if (key === 'take') emit('take')
  else if (key === 'assign') emit('assign')
  else if (key === 'edit') emit('edit')
  else emit('remove')
}
</script>

<template>
  <div class="incident-actions" :class="{ 'incident-actions--inline': isLaptopUp }">
    <IncidentStatusButton
      v-if="canChangeStatus"
      class="incident-actions__primary"
      :next-statuses="incident.nextStatuses"
      :busy="busy"
      @status="emit('status', $event)"
    />

    <template v-if="isLaptopUp">
      <AppButton
        v-for="item in secondary"
        :key="item.key"
        :variant="item.danger ? 'danger' : 'secondary'"
        :icon="item.icon"
        :disabled="busy"
        @click="trigger(item.key)"
      >
        {{ item.label }}
      </AppButton>
    </template>

    <AppButton v-else-if="secondary.length > 0" variant="secondary" :disabled="busy" aria-label="Más acciones">
      <AppIcon name="more" />
      <AppActionMenu :items="secondary" label="Más acciones" align="right" @select="trigger" />
    </AppButton>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-actions {
  display: flex;
  gap: $space-2;
}

// En pantallas chicas el cambio de estado ocupa el ancho disponible
.incident-actions__primary {
  flex: 1;
}

.incident-actions--inline {
  flex-wrap: wrap;
  justify-content: flex-end;

  .incident-actions__primary {
    flex: none;
  }
}
</style>
