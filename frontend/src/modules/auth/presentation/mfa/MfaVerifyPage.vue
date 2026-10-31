<!-- Segundo paso del ingreso: el código de la aplicación de autenticación o, si no se tiene el teléfono, uno de
     recuperación. Sin un ingreso a medias en curso, vuelve al login. Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { fieldMessageFor, messageFor } from '@/shared/errors'
import { ApiError, toApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppCodeField from '@/shared/ui/form/AppCodeField.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import { LOGIN_ROUTE } from '../../application/authGuard'
import { useSessionStore } from '../../application/useSessionStore'
import { safeRedirect } from '../../domain/safeRedirect'
import { MFA_CHALLENGE_EXPIRED, attemptsLeftMessage } from '../../errors'
import AuthPanel from '../AuthPanel.vue'

const route = useRoute()
const router = useRouter()
const session = useSessionStore()

const useRecovery = ref(false)
const code = ref('')
const recoveryCode = ref('')
const error = ref<string | null>(null)
const fieldError = ref<string | undefined>()
// Con el ingreso cerrado (vencido o por intentos) solo queda volver a ingresar
const closed = ref(false)
const submitting = ref(false)

onMounted(() => {
  if (session.pending?.step !== 'mfa_verify') void router.replace({ name: LOGIN_ROUTE })
})

function describe(failure: ApiError): string {
  if (failure.code === 'UNAUTHENTICATED') return messageFor(new ApiError('http', MFA_CHALLENGE_EXPIRED))
  const left = Number(failure.params.attempts_left)
  return left > 0 ? `${messageFor(failure)} ${attemptsLeftMessage(left)}` : messageFor(failure)
}

async function submit(): Promise<void> {
  if (submitting.value) return
  const value = useRecovery.value ? recoveryCode.value.trim() : code.value
  fieldError.value = value ? undefined : fieldMessageFor({ code: 'REQUIRED' })
  if (fieldError.value) return

  submitting.value = true
  error.value = null
  try {
    await session.verifyMfa(useRecovery.value ? { recoveryCode: value } : { code: value })
    await router.replace(safeRedirect(route.query.volver) ?? { name: 'home' })
  } catch (raw) {
    const failure = toApiError(raw)
    error.value = describe(failure)
    closed.value = failure.status === 401
    code.value = ''
  } finally {
    submitting.value = false
  }
}

function switchMethod(): void {
  useRecovery.value = !useRecovery.value
  error.value = null
  fieldError.value = undefined
}

async function restart(): Promise<void> {
  session.clear()
  await router.replace({ name: LOGIN_ROUTE, query: route.query.volver ? { volver: route.query.volver } : {} })
}
</script>

<template>
  <AuthPanel title="Verifica tu identidad">
    <form class="mfa-verify" novalidate @submit.prevent="submit">
      <p class="mfa-verify__lead">
        {{
          useRecovery
            ? 'Escribe uno de los códigos de recuperación que guardaste al activar la verificación en dos pasos.'
            : 'Abre tu aplicación de autenticación y escribe el código de 6 dígitos de Trazo.'
        }}
      </p>

      <AppAlert v-if="error">{{ error }}</AppAlert>

      <template v-if="!closed">
        <AppTextField
          v-if="useRecovery"
          v-model="recoveryCode"
          label="Código de recuperación"
          placeholder="ABCDE-FGHJK"
          autocomplete="off"
          :maxlength="20"
          :error="fieldError"
          autofocus
        />
        <AppCodeField v-else v-model="code" label="Código de verificación" :error="fieldError" autofocus />

        <AppButton type="submit" :loading="submitting" block>Verificar</AppButton>
        <AppButton variant="link" @click="switchMethod">
          {{
            useRecovery ? 'Usar el código de la aplicación' : '¿No tienes tu teléfono? Usa un código de recuperación'
          }}
        </AppButton>
      </template>

      <AppButton :variant="closed ? 'primary' : 'link'" :block="closed" @click="restart">Volver a ingresar</AppButton>
    </form>
  </AuthPanel>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.mfa-verify {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.mfa-verify__lead {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-md;
}
</style>
