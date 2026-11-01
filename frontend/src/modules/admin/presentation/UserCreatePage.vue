<!-- Crear una cuenta: nombre, correo y rol. Nadie define su contraseña: le llega un correo para crearla. Si el rol trae
     permisos de administración, la API pide el código de quien crea y se abre el diálogo. Al terminar, lleva a su
     ficha. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppCodeDialog from '@/shared/ui/feedback/AppCodeDialog.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useAccessCatalogStore } from '../application/useAccessCatalogStore'
import { useUserCreate } from '../application/useUserCreate'
import type { UserDetail } from '../domain/user'
import RoleChoice from './components/RoleChoice.vue'
import UserProfileFields from './components/UserProfileFields.vue'
import { useReauth } from '@/shared/ui/feedback/useReauth'

const router = useRouter()
const session = useSessionStore()
const catalog = useAccessCatalogStore()
const { notify } = useFeedback()
const { dialog, run } = useReauth()

const { draft, fieldErrors, formError, validate, submit } = useUserCreate()
const submitting = ref(false)

async function loadCatalog(): Promise<void> {
  if (session.user) await catalog.load(session.user.id)
}

async function save(): Promise<void> {
  if (submitting.value || !validate()) return
  submitting.value = true
  let created: UserDetail | null = null
  try {
    await run(
      async (code) => {
        created = await submit(code)
      },
      {
        title: 'Confirma que eres tú',
        message:
          'Este rol puede administrar Trazo. Escribe el código de tu aplicación de autenticación para crear la cuenta.',
        confirmLabel: 'Crear cuenta',
      },
    )
  } finally {
    submitting.value = false
  }

  const saved = created as UserDetail | null
  if (!saved) return
  notify(`Cuenta creada. Le enviamos a ${saved.email} un correo para crear su contraseña.`)
  await router.replace({ name: 'user-detail', params: { id: saved.id } })
}

onMounted(loadCatalog)
</script>

<template>
  <div class="user-create">
    <h1 class="user-create__title">Nueva persona</h1>

    <AppErrorState v-if="catalog.error" :error="catalog.error" @retry="loadCatalog" />
    <AppSkeleton v-else-if="catalog.roles.length === 0" :lines="6" />

    <form v-else class="user-create__form" novalidate @submit.prevent="save">
      <AppAlert v-if="formError">{{ formError }}</AppAlert>

      <AppCard>
        <div class="user-create__fields">
          <UserProfileFields v-model="draft" :errors="fieldErrors" autofocus />
          <RoleChoice v-model="draft.role" :roles="catalog.assignableRoles" :error="fieldErrors.role" />
        </div>
      </AppCard>

      <AppAlert tone="info">
        No necesitas definir una contraseña: le llegará un correo para crearla y, al ingresar por primera vez, Trazo le
        pedirá configurar la verificación en dos pasos.
      </AppAlert>

      <div class="user-create__actions">
        <AppButton variant="secondary" @click="router.push({ name: 'users' })">Cancelar</AppButton>
        <AppButton type="submit" :loading="submitting">Crear cuenta</AppButton>
      </div>
    </form>

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

.user-create,
.user-create__form,
.user-create__fields {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.user-create__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

// En celular los botones se apilan, con la acción principal arriba
.user-create__actions {
  display: flex;
  flex-direction: column-reverse;
  gap: $space-2;
}

@include tablet {
  .user-create__actions {
    flex-direction: row;
    justify-content: flex-end;
  }
}

@include laptop {
  .user-create__title {
    font-size: $font-size-2xl;
  }
}
</style>
