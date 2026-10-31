<!-- Permisos de una persona (sobre PermissionChecklist, con su rol como base). Al ver: los que tiene y los que difieren
     de su rol. Al editar: casillas, "Volver a los del rol", Cancelar y Guardar. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { differenceCount } from '../../domain/permissions'
import type { PermissionOption, UserDetail } from '../../domain/user'
import PermissionChecklist from './PermissionChecklist.vue'

const props = defineProps<{
  user: UserDetail
  catalog: PermissionOption[]
  // true si se guardó; false si se canceló al pedir el código
  action: (permissions: string[]) => Promise<boolean>
}>()

const editing = ref(false)
const selected = ref<string[]>([...props.user.permissions.effective])
const saving = ref(false)
const error = ref<string | null>(null)

const rolePermissions = computed(() => props.user.permissions.role)
const differences = computed(() => differenceCount(selected.value, rolePermissions.value))

const summary = computed(() => {
  const role = props.user.role?.name ?? 'su rol'
  if (differences.value === 0) return `Tiene exactamente los permisos de ${role}.`
  return `Permisos de ${role} con ${differences.value} ${differences.value === 1 ? 'cambio' : 'cambios'}.`
})

function startEditing(): void {
  error.value = null
  editing.value = true
}

function cancel(): void {
  selected.value = [...props.user.permissions.effective]
  editing.value = false
}

// Vuelve a los del rol, salvo lo que quien administra no puede conceder
function resetToRole(): void {
  const grantable = new Set(
    props.catalog.filter((permission) => permission.grantable).map((permission) => permission.code),
  )
  selected.value = rolePermissions.value.filter((code) => grantable.has(code) || selected.value.includes(code))
}

async function save(): Promise<void> {
  if (saving.value) return
  saving.value = true
  error.value = null
  try {
    if (await props.action(selected.value)) editing.value = false
  } catch (raw) {
    error.value = messageFor(toApiError(raw))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppCard title="Permisos">
    <template #actions>
      <AppButton v-if="user.actions.grantPermissions && !editing" variant="secondary" icon="edit" @click="startEditing">
        Editar permisos
      </AppButton>
    </template>

    <div class="permissions-card">
      <p class="permissions-card__summary">{{ summary }}</p>
      <AppAlert v-if="error">{{ error }}</AppAlert>

      <PermissionChecklist v-model="selected" :catalog="catalog" :editing="editing" :base="rolePermissions" />

      <div v-if="editing" class="permissions-card__actions">
        <AppButton variant="link" icon="refresh" :disabled="saving" @click="resetToRole">
          Volver a los del rol
        </AppButton>
        <div class="permissions-card__buttons">
          <AppButton variant="secondary" :disabled="saving" @click="cancel">Cancelar</AppButton>
          <AppButton :loading="saving" @click="save">Guardar permisos</AppButton>
        </div>
      </div>
    </div>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.permissions-card {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.permissions-card__summary {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

// En celular los botones se apilan, con la acción principal arriba
.permissions-card__actions,
.permissions-card__buttons {
  display: flex;
  flex-direction: column-reverse;
  gap: $space-2;
}

@include tablet {
  .permissions-card__actions {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .permissions-card__buttons {
    flex-direction: row;
  }
}
</style>
