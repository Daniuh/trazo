<!-- Ficha de una persona: datos, rol, acceso y permisos. Portátil y monitor: datos, rol y acceso a la izquierda y
     permisos a la derecha; celular y tablet: una columna. Los botones salen de lo que permite la API, y si un cambio
     pide el código de quien administra, se abre el diálogo del código. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { auditLocation } from '@/modules/audit/application/auditLink'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { incidentListLocation } from '@/modules/incidents/application/listLink'
import { useCatalogStore } from '@/modules/incidents/application/useCatalogStore'
import { toApiError } from '@/shared/http'
import AppBackLink from '@/shared/ui/AppBackLink.vue'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppCodeDialog from '@/shared/ui/feedback/AppCodeDialog.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useAccessCatalogStore } from '../application/useAccessCatalogStore'
import { useUserDetail } from '../application/useUserDetail'
import type { ProfileDraft } from '../domain/user'
import AccessCard from './components/AccessCard.vue'
import PermissionsCard from './components/PermissionsCard.vue'
import ProfileDialog from './components/ProfileDialog.vue'
import RoleDialog from './components/RoleDialog.vue'
import { useReauth } from '@/shared/ui/feedback/useReauth'
import { roleDescription, statusLabel, statusTone } from './userTexts'

const route = useRoute()
const session = useSessionStore()
const access = useAccessCatalogStore()
const incidentCatalogs = useCatalogStore()
const { confirm, notify, notifyError } = useFeedback()
const { dialog, run } = useReauth()

const detail = useUserDetail(String(route.params.id))
const { user, loading, error } = detail

const editingProfile = ref(false)
const changingRole = ref(false)
const busy = ref(false)

const roleOption = computed(() => access.role(user.value?.role?.code))
const extraPermissions = computed(() =>
  user.value ? user.value.permissions.granted.length + user.value.permissions.denied.length : 0,
)

// Solo quien puede ver todos los incidentes llega al listado con sus pendientes
const pendingLink = computed(() => {
  if (!user.value || !session.can('incidents.view.any')) return null
  const pending = incidentCatalogs.catalogs?.statuses.filter((status) => status.isPending).map((status) => status.code)
  return incidentListLocation({ assignee: [user.value.id], status: pending ?? [] })
})

// Lo que hizo la persona en la auditoría: la administración desde aquí, o sus cambios en incidentes
const activityLink = computed(() => {
  if (!user.value) return null
  if (session.can('audit.admin.view')) return auditLocation('admin', { actor: user.value.id })
  return session.can('audit.incidents.view') ? auditLocation('incidents', { actor: user.value.id }) : null
})

onMounted(() => {
  void detail.load()
  if (session.user) void access.load(session.user.id)
  if (session.can('incidents.view.any')) void incidentCatalogs.load()
})

async function saveProfile(draft: ProfileDraft): Promise<void> {
  const before = user.value?.email
  const saved = await detail.updateProfile(draft)
  notify(saved.email !== before ? 'Datos guardados. Avisamos del cambio al correo anterior.' : 'Datos guardados.')
}

async function saveRole(role: string): Promise<boolean> {
  const done = await run((code) => detail.changeRole(role, code), {
    title: 'Confirma que eres tú',
    message:
      'Este cambio de rol afecta permisos de administración. Escribe el código de tu aplicación de autenticación.',
    confirmLabel: 'Cambiar rol',
  })
  if (done) notify(`Ahora su rol es ${user.value?.role?.name ?? ''}.`)
  return done
}

async function savePermissions(permissions: string[]): Promise<boolean> {
  const done = await run((code) => detail.changePermissions(permissions, code), {
    title: 'Confirma que eres tú',
    message: 'Este cambio toca permisos de administración. Escribe el código de tu aplicación de autenticación.',
    confirmLabel: 'Guardar permisos',
  })
  if (done) notify('Permisos guardados.')
  return done
}

// Acciones de la tarjeta de acceso: los errores se avisan sin salir de la ficha
async function attempt(work: () => Promise<boolean>, success: string): Promise<void> {
  try {
    if (await work()) notify(success)
  } catch (raw) {
    notifyError(toApiError(raw))
  }
}

// Bloquea los botones solo mientras va la petición: con un diálogo abierto, el foco debe poder volver al botón
async function tracked<T>(work: () => Promise<T>): Promise<T> {
  busy.value = true
  try {
    return await work()
  } finally {
    busy.value = false
  }
}

async function deactivate(): Promise<void> {
  const pending = user.value?.pendingAssignedIncidents ?? 0
  const ok = await confirm({
    title: '¿Desactivar esta cuenta?',
    message:
      'No podrá ingresar y se cerrarán sus sesiones abiertas. Su historial se conserva.' +
      (pending > 0
        ? ` Tiene ${pending} ${pending === 1 ? 'incidente pendiente asignado' : 'incidentes pendientes asignados'}: no se reasignan solos.`
        : ''),
    confirmLabel: 'Desactivar',
    danger: true,
  })
  if (ok) await attempt(() => tracked(() => detail.setActive(false).then(() => true)), 'Cuenta desactivada.')
}

const activate = () => attempt(() => tracked(() => detail.setActive(true).then(() => true)), 'Cuenta activada.')

const resetMfa = () =>
  attempt(
    () =>
      run(
        (code) => tracked(() => detail.resetMfa(code)),
        {
          title: '¿Restablecer la verificación en dos pasos?',
          message:
            'Se quitará su verificación en dos pasos y se cerrarán sus sesiones. La configurará de nuevo al ingresar. Escribe el código de tu aplicación para confirmar.',
          confirmLabel: 'Restablecer',
          danger: true,
        },
        true,
      ),
    'Verificación en dos pasos restablecida.',
  )

const resetAccess = () =>
  attempt(
    () =>
      run(
        (code) => tracked(() => detail.resetAccess(code)),
        {
          title: '¿Restablecer el acceso?',
          message:
            'Su contraseña dejará de servir, se cerrarán sus sesiones y le llegará un correo para crear una nueva. Escribe el código de tu aplicación para confirmar.',
          confirmLabel: 'Restablecer acceso',
          danger: true,
        },
        true,
      ),
    'Acceso restablecido. Le enviamos un correo para crear una contraseña nueva.',
  )
</script>

<template>
  <div class="user-detail">
    <AppBackLink :to="{ name: 'users' }" label="Usuarios" />

    <AppSkeleton v-if="loading && !user" :lines="6" />
    <AppErrorState v-else-if="error" :error="error" @retry="detail.load" />

    <template v-else-if="user">
      <header class="user-detail__header">
        <div class="user-detail__identity">
          <h1 class="user-detail__title">{{ user.name }}</h1>
          <div class="user-detail__badges">
            <AppBadge :tone="statusTone(user)">{{ statusLabel(user) }}</AppBadge>
            <AppBadge tone="neutral">{{ user.role?.name ?? 'Sin rol' }}</AppBadge>
          </div>
        </div>
        <AppButton
          v-if="activityLink"
          class="user-detail__activity"
          variant="secondary"
          icon="audit"
          :to="activityLink"
        >
          Ver su actividad
        </AppButton>
      </header>

      <AppAlert v-if="user.isSelf" tone="info">
        Esta es tu cuenta. Puedes corregir tus datos, pero tu rol, tus permisos y tu acceso los cambia otra persona que
        administre Trazo.
      </AppAlert>

      <div class="user-detail__body">
        <div class="user-detail__column">
          <AppCard title="Datos">
            <template #actions>
              <AppButton v-if="user.actions.update" variant="secondary" icon="edit" @click="editingProfile = true">
                Editar
              </AppButton>
            </template>
            <dl class="user-detail__data">
              <dt>Nombre</dt>
              <dd>{{ user.name }}</dd>
              <dt>Correo</dt>
              <dd>{{ user.email }}</dd>
            </dl>
          </AppCard>

          <AppCard title="Rol">
            <template #actions>
              <AppButton v-if="user.actions.assignRole" variant="secondary" @click="changingRole = true">
                Cambiar rol
              </AppButton>
            </template>
            <p class="user-detail__role">{{ user.role?.name ?? 'Sin rol' }}</p>
            <p v-if="roleOption" class="user-detail__hint">{{ roleDescription(roleOption) }}</p>
          </AppCard>

          <AccessCard
            :user="user"
            :busy="busy"
            :pending-link="pendingLink"
            @activate="activate"
            @deactivate="deactivate"
            @reset-mfa="resetMfa"
            @reset-access="resetAccess"
          />
        </div>

        <PermissionsCard
          v-if="access.permissions.length > 0"
          :key="user.permissions.effective.join()"
          :user="user"
          :catalog="access.permissions"
          :action="savePermissions"
        />
      </div>

      <ProfileDialog
        v-model="editingProfile"
        :current="{ givenNames: user.givenNames, familyNames: user.familyNames, email: user.email }"
        :action="saveProfile"
      />
      <RoleDialog
        v-model="changingRole"
        :current="user.role?.code ?? null"
        :roles="access.assignableRoles"
        :extra-permissions="extraPermissions"
        :action="saveRole"
      />
    </template>

    <AppCodeDialog
      v-model="dialog.open"
      :title="dialog.title"
      :message="dialog.message"
      :confirm-label="dialog.confirmLabel"
      :danger="dialog.danger"
      :action="dialog.action"
    />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.user-detail,
.user-detail__column,
.user-detail__body {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

// Celular: nombre, correo y etiquetas; debajo, "Ver su actividad" a todo el ancho
.user-detail__header {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

.user-detail__identity {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  min-width: 0;
}

.user-detail__activity {
  width: 100%;
}

.user-detail__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
  overflow-wrap: anywhere;
}

.user-detail__badges {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
}

.user-detail__data {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: $space-2 $space-4;
  margin: 0;
  font-size: $font-size-sm;

  dt {
    color: $color-text-secondary;
  }

  dd {
    margin: 0;
    overflow-wrap: anywhere;
  }
}

.user-detail__role {
  margin: 0;
  font-weight: $font-weight-semibold;
}

.user-detail__hint {
  margin: $space-1 0 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

// Desde tablet, "Ver su actividad" va a la derecha del nombre
@include tablet {
  .user-detail__header {
    flex-direction: row;
    align-items: flex-start;
    justify-content: space-between;
  }

  .user-detail__activity {
    flex-shrink: 0;
    width: auto;
  }
}

@include laptop {
  .user-detail__title {
    font-size: $font-size-2xl;
  }

  // Permisos ocupa el alto de la columna de la izquierda, hasta el final de Acceso
  .user-detail__body {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: stretch;
  }
}
</style>
