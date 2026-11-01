<!-- Ficha del incidente: quién lo reportó y quién lo atiende (con "Asignar", "Reasignar" o "Tomar" junto a la persona),
     las fechas y, al pie, "Chat (N)", "Editar" y "Eliminar". Editar, eliminar y asignar según lo que la API permite.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatDateTime, formatRelative } from '@/shared/format/dates'
import AppAvatar from '@/shared/ui/AppAvatar.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import type { Incident } from '../../domain/incident'

const props = defineProps<{ incident: Incident; messageCount: number; busy?: boolean }>()
const emit = defineEmits<{ assign: []; take: []; chat: []; edit: []; remove: [] }>()

const dates = computed(() => [
  { label: 'Creado', value: formatDateTime(props.incident.createdAt) },
  { label: 'Vence', value: formatDateTime(props.incident.dueDate) },
  ...(props.incident.resolvedAt ? [{ label: 'Resuelto', value: formatDateTime(props.incident.resolvedAt) }] : []),
  { label: 'Actualizado', value: formatRelative(props.incident.updatedAt) },
])
const can = computed(() => props.incident.actions)
</script>

<template>
  <AppCard title="Ficha" class="incident-profile">
    <div class="incident-profile__people">
      <div class="incident-profile__person">
        <AppAvatar :name="incident.creator.name" size="lg" />
        <div class="incident-profile__who">
          <p class="incident-profile__role">Reportó</p>
          <p class="incident-profile__name">{{ incident.creator.name }}</p>
        </div>
      </div>
      <div class="incident-profile__person">
        <AppAvatar v-if="incident.assignee" :name="incident.assignee.name" size="lg" />
        <span v-else class="incident-profile__empty" aria-hidden="true" />
        <div class="incident-profile__who">
          <p class="incident-profile__role">Atiende</p>
          <p class="incident-profile__name">{{ incident.assignee?.name ?? 'Sin asignar' }}</p>
        </div>
        <AppButton v-if="can.assign" variant="secondary" :disabled="busy" @click="emit('assign')">
          {{ incident.assignee ? 'Reasignar' : 'Asignar' }}
        </AppButton>
        <AppButton v-else-if="can.take" variant="secondary" :disabled="busy" @click="emit('take')"> Tomar </AppButton>
      </div>
    </div>

    <dl class="incident-profile__dates">
      <div v-for="date in dates" :key="date.label" class="incident-profile__date">
        <dt>{{ date.label }}</dt>
        <dd>{{ date.value }}</dd>
      </div>
    </dl>

    <!-- El chat se puede leer siempre: escribir depende del permiso, y eso lo resuelve el panel -->
    <div class="incident-profile__actions">
      <AppButton variant="secondary" icon="chat" @click="emit('chat')">Chat ({{ messageCount }})</AppButton>
      <AppButton v-if="can.update" variant="secondary" icon="edit" :disabled="busy" @click="emit('edit')">
        Editar
      </AppButton>
      <AppButton v-if="can.delete" variant="danger" icon="trash" :disabled="busy" @click="emit('remove')">
        Eliminar
      </AppButton>
    </div>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-profile__people {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

.incident-profile__role,
.incident-profile__name {
  margin: 0;
}

.incident-profile__role {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.incident-profile__dates {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  margin: 0;

  dt {
    color: $color-text-secondary;
    font-size: $font-size-xs;
  }

  dd {
    margin: 0;
  }
}

.incident-profile__person {
  display: flex;
  align-items: center;
  gap: $space-3;
}

.incident-profile__who {
  flex: 1;
  min-width: 0;
}

// Sin persona asignada: el mismo lugar del círculo, punteado
.incident-profile__empty {
  flex: none;
  width: $avatar-size-lg;
  height: $avatar-size-lg;
  border: 1px dashed $color-border-strong;
  border-radius: 50%;
}

.incident-profile__dates {
  gap: $space-2;
  margin-top: $space-4;
  padding-top: $space-4;
  border-top: 1px solid $color-border;
  font-size: $font-size-sm;
}

.incident-profile__date {
  display: flex;
  justify-content: space-between;
  gap: $space-3;

  dt {
    font-size: $font-size-sm;
  }

  dd {
    text-align: right;
  }
}

.incident-profile__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
  margin-top: $space-4;
  padding-top: $space-4;
  border-top: 1px solid $color-border;
}
</style>
