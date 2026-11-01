<!-- Acceso de una persona: estado de la cuenta, verificación en dos pasos, último ingreso e incidentes pendientes que
     tiene asignados. Los botones salen de lo que permite la API (activar o desactivar, restablecer el MFA o el acceso).
     Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import { formatShortDateTime } from '@/shared/format/dates'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import type { UserDetail } from '../../domain/user'
import { lastLoginText, statusLabel, statusTone } from '../userTexts'

defineProps<{ user: UserDetail; busy: boolean; pendingLink: RouteLocationRaw | null }>()
const emit = defineEmits<{ activate: []; deactivate: []; resetMfa: []; resetAccess: [] }>()
</script>

<template>
  <AppCard title="Acceso">
    <dl class="access-card__data">
      <dt>Estado</dt>
      <dd>
        <AppBadge :tone="statusTone(user)">{{ statusLabel(user) }}</AppBadge>
      </dd>
      <dt>Verificación en dos pasos</dt>
      <dd>{{ user.mfaEnabled ? 'Activa' : 'Sin configurar' }}</dd>
      <dt>Último ingreso</dt>
      <dd>{{ lastLoginText(user) }}</dd>
      <dt>Cuenta creada</dt>
      <dd>{{ formatShortDateTime(user.createdAt) }}</dd>
      <dt>Incidentes pendientes asignados</dt>
      <dd class="access-card__pending">
        {{ user.pendingAssignedIncidents }}
        <RouterLink v-if="pendingLink && user.pendingAssignedIncidents > 0" class="access-card__link" :to="pendingLink">
          Ver sus incidentes
        </RouterLink>
      </dd>
    </dl>

    <div
      v-if="user.actions.activate || user.actions.deactivate || user.actions.resetMfa || user.actions.resetAccess"
      class="access-card__actions"
    >
      <AppButton v-if="user.actions.activate" variant="secondary" :disabled="busy" @click="emit('activate')">
        Activar cuenta
      </AppButton>
      <AppButton v-if="user.actions.resetMfa" variant="secondary" :disabled="busy" @click="emit('resetMfa')">
        Restablecer verificación en dos pasos
      </AppButton>
      <div v-if="user.actions.resetAccess" class="access-card__with-tip">
        <AppButton variant="secondary" :disabled="busy" @click="emit('resetAccess')">Restablecer acceso</AppButton>
        <AppInfoTip
          text="Su contraseña deja de servir, se cierran sus sesiones y le llega un correo para crear una nueva."
          label="Qué hace restablecer el acceso"
        />
      </div>
      <AppButton v-if="user.actions.deactivate" variant="danger" :disabled="busy" @click="emit('deactivate')">
        Desactivar cuenta
      </AppButton>
    </div>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.access-card__data {
  display: grid;
  grid-template-columns: 1fr;
  gap: $space-1 $space-4;
  margin: 0;
  font-size: $font-size-sm;

  dt {
    color: $color-text-secondary;
  }

  dd {
    margin: 0 0 $space-2;
  }
}

.access-card__pending {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
}

.access-card__link {
  color: $color-primary;
  font-weight: $font-weight-medium;
}

.access-card__actions {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  margin-top: $space-4;
  padding-top: $space-4;
  border-top: 1px solid $color-border;
}

.access-card__with-tip {
  display: flex;
  align-items: center;
  gap: $space-2;

  > :first-child {
    flex: 1;
  }
}

// Con espacio, cada dato queda en una fila con su nombre a la izquierda
@include tablet {
  .access-card__data {
    grid-template-columns: repeat(2, minmax(0, 1fr));

    dd {
      margin: 0;
    }
  }

  .access-card__actions {
    flex-flow: row wrap;
  }

  .access-card__with-tip > :first-child {
    flex: initial;
  }
}
</style>
