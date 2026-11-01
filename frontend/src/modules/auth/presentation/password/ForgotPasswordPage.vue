<!-- Recuperar la contraseña, paso 1: el correo. La respuesta es la misma exista o no la cuenta, así que siempre se
     pasa al paso del código. Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { fieldMessageFor, fieldMessagesFor, messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import { looksLikeEmail } from '@/shared/validation/email'
import { LOGIN_ROUTE } from '../../application/authGuard'
import { useRecoveryStore } from '../../application/useRecoveryStore'
import AuthPanel from '../AuthPanel.vue'

const router = useRouter()
const recovery = useRecoveryStore()

const email = ref(recovery.email)
const fieldError = ref<string | undefined>()
const error = ref<string | null>(null)
const sending = ref(false)

async function submit(): Promise<void> {
  if (sending.value) return
  const value = email.value.trim()
  fieldError.value = !value
    ? fieldMessageFor({ code: 'REQUIRED' })
    : looksLikeEmail(value)
      ? undefined
      : fieldMessageFor({ code: 'INVALID_EMAIL' })
  if (fieldError.value) return

  sending.value = true
  error.value = null
  try {
    await recovery.request(value)
    await router.push({ name: 'password-code' })
  } catch (raw) {
    const failure = toApiError(raw)
    fieldError.value = fieldMessagesFor(failure).email
    error.value = failure.isValidation ? null : messageFor(failure)
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <AuthPanel title="Recupera tu contraseña">
    <form class="forgot" novalidate @submit.prevent="submit">
      <p class="forgot__lead">
        Escribe el correo de tu cuenta y te enviaremos un código de 6 dígitos para crear una contraseña nueva.
      </p>
      <AppAlert v-if="error">{{ error }}</AppAlert>
      <AppTextField
        v-model="email"
        label="Correo electrónico"
        type="email"
        placeholder="nombre@empresa.com"
        autocomplete="username"
        :maxlength="191"
        :error="fieldError"
        autofocus
      />
      <AppButton type="submit" :loading="sending" block>Enviar código</AppButton>
      <RouterLink class="forgot__link" :to="{ name: LOGIN_ROUTE }">Volver a ingresar</RouterLink>
    </form>
  </AuthPanel>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.forgot {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.forgot__lead {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-md;
}

.forgot__link {
  align-self: center;
  color: $color-primary;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}
</style>
