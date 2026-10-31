<!-- Crear un rol o ver y editar uno existente. Portátil y monitor: datos y política de MFA lado a lado y los permisos
     debajo, a lo ancho y en columnas; celular y tablet: una columna. Si el rol no se puede editar (el de la cima o uno de nivel igual o mayor),
     se muestra sin campos. Editar un rol con personas pide confirmación; si la API pide el código de quien administra,
     se abre el diálogo del código. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import AppBackLink from '@/shared/ui/AppBackLink.vue'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppCodeDialog from '@/shared/ui/feedback/AppCodeDialog.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { toApiError } from '@/shared/http'
import { useAccessCatalogStore } from '../application/useAccessCatalogStore'
import { useRoleForm } from '../application/useRoles'
import { draftChanged, draftOf, ROOT_LEVEL, type Role } from '../domain/role'
import PermissionChecklist from './components/PermissionChecklist.vue'
import RoleFields from './components/RoleFields.vue'
import { useReauth } from '@/shared/ui/feedback/useReauth'
import { membersText, mfaPolicyText } from './userTexts'

const route = useRoute()
const router = useRouter()
const session = useSessionStore()
const catalog = useAccessCatalogStore()
const { confirm, notify, notifyError } = useFeedback()
const { dialog, run } = useReauth()

const code = route.params.code ? String(route.params.code) : null
const form = useRoleForm(code)
const { role, draft, loading, loadError, fieldErrors, formError } = form
const saving = ref(false)

const creating = code === null
const editable = computed(() => creating || (role.value?.actions.update ?? false))
const readOnlyReason = computed(() =>
  (role.value?.level ?? 0) >= ROOT_LEVEL
    ? 'El rol Super admin tiene siempre todos los permisos y no se edita.'
    : 'Este rol está en tu nivel o por encima, así que solo lo puedes ver.',
)

// Copiar permisos de otro rol, para no empezar desde cero
const copyOptions = computed(() => catalog.roles.map((item) => ({ label: item.name, value: item.code })))
const copyFrom = computed({
  get: () => null,
  set: (value: string | string[] | null) => {
    const source = catalog.role(typeof value === 'string' ? value : null)
    if (source) draft.value = { ...draft.value, permissions: [...source.permissions] }
  },
})

async function loadAll(): Promise<void> {
  await Promise.all([form.load(), session.user ? catalog.load(session.user.id) : Promise.resolve()])
}

async function confirmImpact(current: Role): Promise<boolean> {
  if (current.members === 0) return true
  return confirm({
    title: '¿Guardar los cambios del rol?',
    message: `Afecta a ${membersText(current.members)} con este rol: sus permisos cambian desde su próxima acción.`,
    confirmLabel: 'Guardar cambios',
  })
}

async function save(): Promise<void> {
  if (saving.value || !form.validate()) return
  const current = role.value
  if (current && !draftChanged(draft.value, current)) {
    notify('No hay cambios que guardar.')
    return
  }
  if (current && !(await confirmImpact(current))) return

  saving.value = true
  let saved: Role | null = null
  try {
    await run(
      async (mfaCode) => {
        saved = await form.save(mfaCode)
      },
      {
        title: 'Confirma que eres tú',
        message:
          'Este cambio toca permisos de administración, el nivel del rol o su verificación en dos pasos. Escribe el código de tu aplicación de autenticación.',
        confirmLabel: creating ? 'Crear rol' : 'Guardar cambios',
      },
    )
  } catch (raw) {
    form.showError(toApiError(raw))
  } finally {
    saving.value = false
  }

  const result = saved as Role | null
  if (!result) return
  if (creating) {
    notify(`Rol ${result.name} creado.`)
    await router.replace({ name: 'role-detail', params: { code: result.code } })
  } else {
    draft.value = draftOf(result)
    notify('Cambios guardados.')
  }
}

async function remove(): Promise<void> {
  const ok = await confirm({
    title: '¿Eliminar este rol?',
    message: 'Se elimina con sus permisos y su política. El registro de la administración conserva su historial.',
    confirmLabel: 'Eliminar',
    danger: true,
  })
  if (!ok) return
  const failure = await form.remove()
  if (failure) {
    notifyError(failure)
    return
  }
  notify('Rol eliminado.')
  await router.replace({ name: 'roles' })
}

onMounted(loadAll)
</script>

<template>
  <div class="role-form">
    <AppBackLink :to="{ name: 'roles' }" label="Roles" />

    <AppErrorState v-if="loadError ?? catalog.error" :error="(loadError ?? catalog.error)!" @retry="loadAll" />
    <AppSkeleton v-else-if="loading || catalog.permissions.length === 0" :lines="6" />

    <template v-else>
      <header class="role-form__header">
        <h1 class="role-form__title">{{ creating ? 'Nuevo rol' : role?.name }}</h1>
        <div v-if="role" class="role-form__badges">
          <AppBadge v-if="role.isSystem" tone="neutral">Base</AppBadge>
          <AppBadge tone="blue">Nivel {{ role.level }}</AppBadge>
          <span class="role-form__members">{{ membersText(role.members) }}</span>
        </div>
      </header>

      <AppAlert v-if="!editable" tone="info">{{ readOnlyReason }}</AppAlert>
      <AppAlert v-if="formError">{{ formError }}</AppAlert>

      <form class="role-form__body" novalidate @submit.prevent="save">
        <div class="role-form__column">
          <RoleFields v-if="editable" v-model="draft" :creating="creating" :errors="fieldErrors" />
          <AppCard v-else-if="role" title="Datos">
            <dl class="role-form__data">
              <dt>Código</dt>
              <dd>{{ role.code }}</dd>
              <dt>Nivel</dt>
              <dd>{{ role.level }}</dd>
              <dt>Verificación en dos pasos</dt>
              <dd>{{ mfaPolicyText(role.mfa) }}</dd>
            </dl>
          </AppCard>
        </div>

        <AppCard title="Permisos">
          <div class="role-form__permissions">
            <AppSelect
              v-if="creating"
              v-model="copyFrom"
              label="Copiar permisos de"
              :options="copyOptions"
              placeholder="Elige un rol"
            />
            <PermissionChecklist
              v-model="draft.permissions"
              :catalog="catalog.permissions"
              :editing="editable"
              columns
            />
          </div>
        </AppCard>

        <div v-if="editable" class="role-form__actions">
          <AppButton v-if="role?.actions.delete" variant="danger" :disabled="saving" @click="remove">
            Eliminar rol
          </AppButton>
          <div class="role-form__buttons">
            <AppButton variant="secondary" :disabled="saving" @click="router.push({ name: 'roles' })">
              Cancelar
            </AppButton>
            <AppButton type="submit" :loading="saving">{{ creating ? 'Crear rol' : 'Guardar cambios' }}</AppButton>
          </div>
        </div>
      </form>
    </template>

    <AppCodeDialog
      v-model="dialog.open"
      :title="dialog.title"
      :message="dialog.message"
      :confirm-label="dialog.confirmLabel"
      :action="dialog.action"
    />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.role-form,
.role-form__body,
.role-form__column,
.role-form__permissions {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.role-form__header {
  display: flex;
  flex-direction: column;
  gap: $space-2;
}

.role-form__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
  overflow-wrap: anywhere;
}

.role-form__badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2;
}

.role-form__members {
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

.role-form__data {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: $space-2 $space-4;
  margin: 0;
  font-size: $font-size-sm;

  dt {
    color: $color-text-secondary;
  }

  dd {
    margin: 0;
  }
}

// En celular los botones se apilan, con la acción principal arriba
.role-form__actions,
.role-form__buttons {
  display: flex;
  flex-direction: column-reverse;
  gap: $space-2;
}

@include tablet {
  .role-form__actions {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  // Sin "Eliminar", los botones van a la derecha
  .role-form__buttons {
    flex-direction: row;
    margin-left: auto;
  }
}

@include laptop {
  .role-form__title {
    font-size: $font-size-2xl;
  }

  // Datos y verificación lado a lado; los permisos debajo, a lo ancho, para repartir sus módulos en columnas
  .role-form__column {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: stretch;

    > :only-child {
      grid-column: 1 / -1;
    }
  }
}
</style>
