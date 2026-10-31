<!-- Pantalla de inicio de sesión, dentro del marco de las pantallas sin sesión (AuthPanel). -->
<!-- La validación aquí es solo visual: quien decide es la API. Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { fieldMessageFor, fieldMessagesFor, messageFor } from '@/shared/errors'
import { ApiError, toApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import { SESSION_EXPIRED_REASON } from '../application/authGuard'
import { useSessionStore } from '../application/useSessionStore'
import { safeRedirect } from '../domain/safeRedirect'
import AuthPanel from './AuthPanel.vue'

const EMAIL_SHAPE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const route = useRoute()
const router = useRouter()
const session = useSessionStore()

const form = reactive({ email: '', password: '' })
const fieldErrors = ref<Record<string, string>>({})
const formError = ref<string | null>(null)
const submitting = ref(false)

const sessionExpired = computed(() => route.query.motivo === SESSION_EXPIRED_REASON && !formError.value)
const expiredMessage = messageFor(new ApiError('http', 'UNAUTHENTICATED'))

// Revisión rápida antes de enviar, para no esperar a la API por un campo vacío
function localErrors(): Record<string, string> {
  const errors: Record<string, string> = {}
  if (!form.email.trim()) errors.email = fieldMessageFor({ code: 'REQUIRED' })
  else if (!EMAIL_SHAPE.test(form.email.trim())) errors.email = fieldMessageFor({ code: 'INVALID_EMAIL' })
  if (!form.password) errors.password = fieldMessageFor({ code: 'REQUIRED' })
  return errors
}

async function submit(): Promise<void> {
  // Un segundo Enter mientras se espera la respuesta no debe contar como otro intento
  if (submitting.value) return
  formError.value = null
  fieldErrors.value = localErrors()
  if (Object.keys(fieldErrors.value).length > 0) return

  submitting.value = true
  try {
    await session.login(form.email, form.password)
    await router.replace(safeRedirect(route.query.volver) ?? { name: 'home' })
  } catch (raw) {
    const error = toApiError(raw)
    fieldErrors.value = fieldMessagesFor(error)
    formError.value = error.isValidation ? null : messageFor(error)
    form.password = ''
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthPanel title="Inicia sesión">
    <form class="login__form" novalidate @submit.prevent="submit">
      <AppAlert v-if="formError">{{ formError }}</AppAlert>
      <AppAlert v-else-if="sessionExpired" tone="info">{{ expiredMessage }}</AppAlert>

      <AppTextField
        v-model="form.email"
        label="Correo electrónico"
        type="email"
        placeholder="nombre@empresa.com"
        autocomplete="username"
        :maxlength="191"
        :error="fieldErrors.email"
        autofocus
      />
      <AppTextField
        v-model="form.password"
        label="Contraseña"
        type="password"
        autocomplete="current-password"
        :maxlength="255"
        :error="fieldErrors.password"
      />

      <AppButton type="submit" :loading="submitting" block>Ingresar</AppButton>
    </form>
  </AuthPanel>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.login__form {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}
</style>
