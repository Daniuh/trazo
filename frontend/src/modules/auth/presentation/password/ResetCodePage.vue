<!-- Recuperar la contraseña, paso 2: el código de 6 dígitos que llegó al correo. Se puede reenviar pasado un minuto.
     Sin un correo en curso, vuelve al paso 1. Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { fieldMessageFor, messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppCodeField from '@/shared/ui/form/AppCodeField.vue'
import { useRecoveryStore } from '../../application/useRecoveryStore'
import AuthPanel from '../AuthPanel.vue'

// Espera antes de poder pedir otro código, para no llenar la bandeja (la API además limita a 3 cada 15 minutos)
const RESEND_AFTER_SECONDS = 60

const router = useRouter()
const recovery = useRecoveryStore()

const code = ref('')
const fieldError = ref<string | undefined>()
const error = ref<string | null>(null)
const notice = ref<string | null>(null)
const verifying = ref(false)
const resending = ref(false)
const waitSeconds = ref(RESEND_AFTER_SECONDS)
let timer: ReturnType<typeof setInterval> | undefined

const resendLabel = computed(() =>
  waitSeconds.value > 0 ? `Reenviar código en ${waitSeconds.value} s` : 'Reenviar código',
)

function startCountdown(): void {
  waitSeconds.value = RESEND_AFTER_SECONDS
  clearInterval(timer)
  timer = setInterval(() => {
    waitSeconds.value = Math.max(0, waitSeconds.value - 1)
    if (waitSeconds.value === 0) clearInterval(timer)
  }, 1000)
}

onMounted(() => {
  if (!recovery.email) void router.replace({ name: 'password-forgot' })
  else startCountdown()
})
onBeforeUnmount(() => clearInterval(timer))

async function submit(): Promise<void> {
  if (verifying.value) return
  fieldError.value = code.value.length === 6 ? undefined : fieldMessageFor({ code: 'REQUIRED' })
  if (fieldError.value) return

  verifying.value = true
  error.value = null
  notice.value = null
  try {
    await recovery.verify(code.value)
    await router.push({ name: 'password-new' })
  } catch (raw) {
    error.value = messageFor(toApiError(raw))
    code.value = ''
  } finally {
    verifying.value = false
  }
}

async function resend(): Promise<void> {
  resending.value = true
  error.value = null
  try {
    await recovery.request(recovery.email)
    notice.value = 'Te enviamos un código nuevo. El anterior ya no sirve.'
    startCountdown()
  } catch (raw) {
    error.value = messageFor(toApiError(raw))
  } finally {
    resending.value = false
  }
}
</script>

<template>
  <AuthPanel title="Revisa tu correo">
    <form class="reset-code" novalidate @submit.prevent="submit">
      <p class="reset-code__lead">
        Si <strong>{{ recovery.email }}</strong> está registrado, te enviamos un código de 6 dígitos. Vence en 15
        minutos.
      </p>
      <AppAlert v-if="error">{{ error }}</AppAlert>
      <AppAlert v-else-if="notice" tone="info">{{ notice }}</AppAlert>
      <AppCodeField v-model="code" label="Código del correo" :error="fieldError" autofocus />
      <AppButton type="submit" :loading="verifying" block>Continuar</AppButton>
      <div class="reset-code__links">
        <AppButton variant="link" :disabled="waitSeconds > 0" :loading="resending" @click="resend">
          {{ resendLabel }}
        </AppButton>
        <RouterLink class="reset-code__link" :to="{ name: 'password-forgot' }">Usar otro correo</RouterLink>
      </div>
    </form>
  </AuthPanel>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.reset-code {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.reset-code__lead {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-md;
  overflow-wrap: anywhere;
}

.reset-code__links {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-2;
}

.reset-code__link {
  color: $color-primary;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}
</style>
