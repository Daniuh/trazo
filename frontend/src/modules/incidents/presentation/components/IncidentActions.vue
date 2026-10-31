<!-- Acciones del detalle, según lo que la API permite ("actions"). Portátil y monitor: botones en línea.
     Celular y tablet: "Cambiar estado" como botón principal y el resto en el menú ⋯. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { ClosePopup, QMenu } from 'quasar'
import { computed } from 'vue'
import type { IconName } from '@/design/icons/icons'
import AppButton from '@/shared/ui/AppButton.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import { useViewport } from '@/shared/ui/useViewport'
import type { Incident } from '../../domain/incident'

const props = defineProps<{ incident: Incident; busy?: boolean }>()
const emit = defineEmits<{ status: [code: string]; take: []; assign: []; edit: []; remove: [] }>()

const { isLaptopUp } = useViewport()
// Al elegir una opción, el menú se cierra solo
const vClosePopup = ClosePopup

type Secondary = { key: 'take' | 'assign' | 'edit' | 'remove'; label: string; icon: IconName; danger?: boolean }

const secondary = computed<Secondary[]>(() => {
  const can = props.incident.actions
  const items: (Secondary | false)[] = [
    can.take && { key: 'take', label: 'Tomar', icon: 'user' },
    can.assign && { key: 'assign', label: props.incident.assignee ? 'Reasignar' : 'Asignar', icon: 'users' },
    can.update && { key: 'edit', label: 'Editar', icon: 'edit' },
    can.delete && { key: 'remove', label: 'Eliminar', icon: 'trash', danger: true },
  ]
  return items.filter((item): item is Secondary => item !== false)
})

const canChangeStatus = computed(() => props.incident.actions.changeStatus && props.incident.nextStatuses.length > 0)

function trigger(key: Secondary['key']): void {
  if (key === 'take') emit('take')
  else if (key === 'assign') emit('assign')
  else if (key === 'edit') emit('edit')
  else emit('remove')
}
</script>

<template>
  <div class="incident-actions" :class="{ 'incident-actions--inline': isLaptopUp }">
    <AppButton v-if="canChangeStatus" class="incident-actions__primary" :loading="busy" icon="refresh">
      Cambiar estado
      <QMenu anchor="bottom left" self="top left" :offset="[0, 6]">
        <div class="incident-actions__menu" role="menu" aria-label="Estados disponibles">
          <button
            v-for="status in incident.nextStatuses"
            :key="status.code"
            v-close-popup
            class="incident-actions__menu-item"
            type="button"
            role="menuitem"
            @click="emit('status', status.code)"
          >
            {{ status.name }}
          </button>
        </div>
      </QMenu>
    </AppButton>

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
      <QMenu anchor="bottom right" self="top right" :offset="[0, 6]">
        <div class="incident-actions__menu" role="menu" aria-label="Más acciones">
          <button
            v-for="item in secondary"
            :key="item.key"
            v-close-popup
            class="incident-actions__menu-item"
            :class="{ 'incident-actions__menu-item--danger': item.danger }"
            type="button"
            role="menuitem"
            @click="trigger(item.key)"
          >
            <AppIcon :name="item.icon" />
            {{ item.label }}
          </button>
        </div>
      </QMenu>
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

.incident-actions__menu {
  display: flex;
  flex-direction: column;
  min-width: 12rem;
  padding: $space-1 0;
}

.incident-actions__menu-item {
  display: flex;
  align-items: center;
  gap: $space-2;
  min-height: $touch-target;
  padding: 0 $space-4;
  border: 0;
  background: none;
  color: $color-text;
  font: inherit;
  font-size: $font-size-sm;
  text-align: left;
  cursor: pointer;

  &:hover {
    background: $color-page;
  }
}

.incident-actions__menu-item--danger {
  color: $color-danger;
}
</style>
