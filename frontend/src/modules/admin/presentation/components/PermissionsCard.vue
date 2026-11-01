<!-- Permisos de una persona, agrupados por módulo. Al ver: los que tiene, y los que difieren de su rol marcados como
     "Adicional" o "Quitado del rol". Al editar: una casilla por permiso; los que quien administra no tiene y la persona
     tampoco quedan deshabilitados (el ícono de información explica por qué). Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppCheckbox from '@/shared/ui/form/AppCheckbox.vue'
import { canToggle, differenceCount, differenceFromRole, groupByModule } from '../../domain/permissions'
import type { PermissionOption, UserDetail } from '../../domain/user'

const props = defineProps<{
  user: UserDetail
  catalog: PermissionOption[]
  // true si se guardó; false si se canceló al pedir el código
  action: (permissions: string[]) => Promise<boolean>
}>()

const editing = ref(false)
const selected = ref<string[]>([])
const saving = ref(false)
const error = ref<string | null>(null)

const rolePermissions = computed(() => props.user.permissions.role)
const current = computed(() => (editing.value ? selected.value : props.user.permissions.effective))
const groups = computed(() => groupByModule(props.catalog))
const differences = computed(() => differenceCount(current.value, rolePermissions.value))

// Al ver, solo se listan los que tiene y los que se le quitaron
const visible = (code: string) =>
  editing.value ||
  current.value.includes(code) ||
  differenceFromRole(code, current.value, rolePermissions.value) !== null

const summary = computed(() => {
  const role = props.user.role?.name ?? 'su rol'
  if (differences.value === 0) return `Tiene exactamente los permisos de ${role}.`
  return `Permisos de ${role} con ${differences.value} ${differences.value === 1 ? 'cambio' : 'cambios'}.`
})

function startEditing(): void {
  selected.value = [...props.user.permissions.effective]
  error.value = null
  editing.value = true
}

function toggle(code: string, on: boolean): void {
  selected.value = on ? [...selected.value, code] : selected.value.filter((item) => item !== code)
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

      <section v-for="group in groups" :key="group.module" class="permissions-card__group">
        <template v-if="group.items.some((permission) => visible(permission.code))">
          <h3 class="permissions-card__module">{{ group.label }}</h3>
          <ul class="permissions-card__list">
            <template v-for="permission in group.items" :key="permission.code">
              <li v-if="visible(permission.code)" class="permissions-card__item">
                <AppCheckbox
                  v-if="editing"
                  :model-value="selected.includes(permission.code)"
                  :label="permission.name"
                  :disabled="!canToggle(permission, selected)"
                  @update:model-value="toggle(permission.code, $event)"
                />
                <span
                  v-else
                  class="permissions-card__name"
                  :class="{ 'permissions-card__name--removed': !current.includes(permission.code) }"
                >
                  <AppIcon :name="current.includes(permission.code) ? 'check' : 'close'" size="sm" />
                  {{ permission.name }}
                </span>

                <AppInfoTip
                  v-if="editing && !canToggle(permission, selected)"
                  text="No tienes este permiso, así que no lo puedes conceder."
                  :label="`Por qué no se puede conceder ${permission.name}`"
                />
                <AppBadge v-if="differenceFromRole(permission.code, current, rolePermissions) === 'added'" tone="blue">
                  Adicional
                </AppBadge>
                <AppBadge
                  v-else-if="differenceFromRole(permission.code, current, rolePermissions) === 'removed'"
                  tone="orange"
                >
                  Quitado del rol
                </AppBadge>
              </li>
            </template>
          </ul>
        </template>
      </section>

      <div v-if="editing" class="permissions-card__actions">
        <AppButton variant="link" icon="refresh" :disabled="saving" @click="resetToRole"
          >Volver a los del rol</AppButton
        >
        <div class="permissions-card__buttons">
          <AppButton variant="secondary" :disabled="saving" @click="editing = false">Cancelar</AppButton>
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

.permissions-card__module {
  margin: 0 0 $space-2;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
}

.permissions-card__list {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  margin: 0;
  padding: 0;
  list-style: none;
}

.permissions-card__item {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2;
  font-size: $font-size-sm;
}

.permissions-card__name {
  display: inline-flex;
  align-items: center;
  gap: $space-2;
}

.permissions-card__name--removed {
  color: $color-text-secondary;
  text-decoration: line-through;
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
