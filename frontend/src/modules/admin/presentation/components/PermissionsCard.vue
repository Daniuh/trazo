<!-- Permisos de una persona, con su rol como base. La tarjeta no crece: en celular y tablet muestra hasta cinco diferencias
     con el rol; en portátil y monitor, todas las que quepan en su alto (el de la columna de al lado). "Ver todos" y "Editar permisos" abren un panel (AppSheet) con la lista completa, que se desplaza sola; al
     editar, el panel trae "Volver a los del rol", Cancelar y Guardar siempre a la vista. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppSheet from '@/shared/ui/AppSheet.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useFitCount } from '@/shared/ui/useFitCount'
import { useViewport } from '@/shared/ui/useViewport'
import { differenceCount, roleDifferences } from '../../domain/permissions'
import type { PermissionOption, UserDetail } from '../../domain/user'
import PermissionChecklist from './PermissionChecklist.vue'

// Diferencias con el rol que se ven en celular y tablet; el resto se ve en el panel
const PREVIEW_LIMIT = 5

const props = defineProps<{
  user: UserDetail
  catalog: PermissionOption[]
  // true si se guardó; false si se canceló al pedir el código
  action: (permissions: string[]) => Promise<boolean>
}>()

const effective = computed(() => props.user.permissions.effective)
const rolePermissions = computed(() => props.user.permissions.role)

const mode = ref<'view' | 'edit' | null>(null)
const selected = ref<string[]>([...effective.value])
const saving = ref(false)
const error = ref<string | null>(null)

const differences = computed(() => roleDifferences(effective.value, rolePermissions.value, props.catalog))
// En portátil y monitor la tarjeta mide lo que la columna de al lado: se muestran las que quepan
const { isLaptopUp } = useViewport()
const changesList = ref<HTMLElement | null>(null)
const { fit } = useFitCount(changesList, isLaptopUp)
const limit = computed(() => (isLaptopUp.value ? (fit.value ?? differences.value.length) : PREVIEW_LIMIT))
const preview = computed(() => differences.value.slice(0, limit.value))
const hiddenCount = computed(() => differences.value.length - preview.value.length)

function summaryOf(permissions: string[]): string {
  const role = props.user.role?.name ?? 'su rol'
  const count = differenceCount(permissions, rolePermissions.value)
  if (count === 0) return `Tiene exactamente los permisos de ${role}.`
  return `Permisos de ${role} con ${count} ${count === 1 ? 'cambio' : 'cambios'}.`
}

const sheetOpen = computed({
  get: () => mode.value !== null,
  set: (open: boolean) => {
    if (!open) close()
  },
})

function view(): void {
  mode.value = 'view'
}

function startEditing(): void {
  selected.value = [...effective.value]
  error.value = null
  mode.value = 'edit'
}

function close(): void {
  if (saving.value) return
  mode.value = null
  selected.value = [...effective.value]
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
    if (await props.action(selected.value)) mode.value = null
  } catch (raw) {
    error.value = messageFor(toApiError(raw))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppCard class="permissions-card" title="Permisos">
    <template #actions>
      <AppButton v-if="user.actions.grantPermissions" variant="secondary" icon="edit" @click="startEditing">
        Editar permisos
      </AppButton>
    </template>

    <div class="permissions-card__content">
      <p class="permissions-card__summary">{{ summaryOf(effective) }}</p>

      <ul
        v-if="differences.length > 0"
        ref="changesList"
        class="permissions-card__changes"
        aria-label="Cambios frente al rol"
      >
        <li v-for="change in preview" :key="change.code" class="permissions-card__change">
          <AppIcon :name="change.kind === 'added' ? 'check' : 'close'" size="sm" />
          <span class="permissions-card__name" :title="change.name">{{ change.name }}</span>
          <AppBadge :tone="change.kind === 'added' ? 'blue' : 'orange'">
            {{ change.kind === 'added' ? 'Adicional' : 'Quitado del rol' }}
          </AppBadge>
        </li>
      </ul>
      <!-- En portátil la línea guarda su lugar aunque esté vacía, para que el espacio de la lista no cambie -->
      <p
        v-if="hiddenCount > 0 || isLaptopUp"
        class="permissions-card__summary"
        :class="{ 'permissions-card__more--empty': hiddenCount === 0 }"
      >
        Y {{ hiddenCount }} {{ hiddenCount === 1 ? 'cambio más' : 'cambios más' }}.
      </p>

      <AppButton class="permissions-card__all" variant="secondary" icon="eye" @click="view">
        Ver todos los permisos ({{ effective.length }})
      </AppButton>
    </div>

    <AppSheet v-model:open="sheetOpen" :title="mode === 'edit' ? 'Editar permisos' : `Permisos de ${user.name}`">
      <p class="permissions-card__summary">{{ summaryOf(selected) }}</p>
      <AppAlert v-if="error">{{ error }}</AppAlert>
      <PermissionChecklist v-model="selected" :catalog="catalog" :editing="mode === 'edit'" :base="rolePermissions" />

      <template v-if="mode === 'edit'" #footer>
        <div class="permissions-card__actions">
          <AppButton variant="link" icon="refresh" :disabled="saving" @click="resetToRole">
            Volver a los del rol
          </AppButton>
          <div class="permissions-card__buttons">
            <AppButton variant="secondary" :disabled="saving" @click="close">Cancelar</AppButton>
            <AppButton :loading="saving" @click="save">Guardar permisos</AppButton>
          </div>
        </div>
      </template>
    </AppSheet>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.permissions-card__content {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

.permissions-card__more--empty {
  visibility: hidden;
}

.permissions-card__summary {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

.permissions-card__changes {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  margin: 0;
  padding: 0;
  list-style: none;
}

.permissions-card__change {
  display: flex;
  align-items: center;
  gap: $space-2;
  font-size: $font-size-sm;
}

.permissions-card__name {
  flex: 1;
  min-width: 0;
}

.permissions-card__all {
  align-self: flex-start;
}

// En el pie del panel: "Volver a los del rol" arriba y los botones abajo, con la acción principal a la derecha
.permissions-card__actions {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: $space-2;
}

.permissions-card__buttons {
  display: flex;
  justify-content: flex-end;
  gap: $space-2;
  width: 100%;
}

// Portátil y monitor: la tarjeta toma el alto de la columna de al lado (`contain: size` evita que su contenido la
// estire) y la lista ocupa el espacio libre, con una línea por permiso
@include laptop {
  .permissions-card {
    display: flex;
    flex-direction: column;
    contain: size;
  }

  .permissions-card__content {
    flex: 1;
    min-height: 0;
  }

  .permissions-card__changes {
    flex: 1 1 0;
    min-height: 0;
    overflow: hidden;
  }

  .permissions-card__name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}
</style>
