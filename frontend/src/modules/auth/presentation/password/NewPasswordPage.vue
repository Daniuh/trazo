<!-- Recuperar la contraseña, paso 3: la contraseña nueva y, si la cuenta tiene MFA, el código de la aplicación o uno
     de recuperación. Al terminar vuelve al login con un aviso. Sin pase, vuelve al paso 1.
     Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { fieldMessagesFor, messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppCodeField from '@/shared/ui/form/AppCodeField.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import { LOGIN_ROUTE, PASSWORD_CHANGED_REASON } from '../../application/authGuard'
import { useRecoveryStore } from '../../application/useRecoveryStore'
import AuthPanel from '../AuthPanel.vue'
import NewPasswordFields from './NewPasswordFields.vue'

const router = useRouter()
const recovery = useRecoveryStore()

const form = reactive({ password: '', confirmation: '', mfaCode: '', recoveryCode: '' })
const useRecoveryCode = ref(false)
const fieldErrors = ref<Record<string, string>>({})
const error = ref<string | null>(null)
// El pase venció o se usó: solo queda pedir un código nuevo
const expired = ref(false)
const saving = ref(false)

onMounted(() => {
  if (!recovery.ticket) void router.replace({ name: 'password-forgot' })
})

async function submit(): Promise<void> {
  if (saving.value) return
  saving.value = true
  error.value = null
  fieldErrors.value = {}
  try {
    await recovery.reset({
      password: form.password,
      confirmation: form.confirmation,
      mfaCode: useRecoveryCode.value ? undefined : form.mfaCode,
      recoveryCode: useRecoveryCode.value ? form.recoveryCode : undefined,
    })
    await router.replace({ name: LOGIN_ROUTE, query: { motivo: PASSWORD_CHANGED_REASON } })
  } catch (raw) {
    const failure = toApiError(raw)
    fieldErrors.value = fieldMessagesFor(failure)
    error.value = failure.isValidation ? null : messageFor(failure)
    expired.value = failure.code === 'PASSWORD_RESET_EXPIRED'
    form.mfaCode = ''
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AuthPanel title="Crea tu contraseña nueva">
    <form class="new-password-page" novalidate @submit.prevent="submit">
      <AppAlert v-if="error">{{ error }}</AppAlert>

      <template v-if="!expired">
        <NewPasswordFields
          v-model:password="form.password"
          v-model:confirmation="form.confirmation"
          :errors="fieldErrors"
          autofocus
        />

        <section v-if="recovery.mfaRequired" class="new-password-page__mfa" aria-labelledby="new-password-mfa">
          <h2 id="new-password-mfa" class="new-password-page__subtitle">Verificación en dos pasos</h2>
          <AppTextField
            v-if="useRecoveryCode"
            v-model="form.recoveryCode"
            label="Código de recuperación"
            placeholder="ABCDE-FGHJK"
            autocomplete="off"
            :maxlength="20"
            :error="fieldErrors.recovery_code"
          />
          <AppCodeField v-else v-model="form.mfaCode" label="Código de la aplicación" :error="fieldErrors.mfa_code" />
          <AppButton variant="link" @click="useRecoveryCode = !useRecoveryCode">
            {{
              useRecoveryCode
                ? 'Usar el código de la aplicación'
                : '¿No tienes tu teléfono? Usa un código de recuperación'
            }}
          </AppButton>
        </section>

        <AppButton type="submit" :loading="saving" block>Guardar contraseña</AppButton>
      </template>

      <RouterLink class="new-password-page__link" :to="{ name: 'password-forgot' }">
        {{ expired ? 'Pedir un código nuevo' : 'Empezar de nuevo' }}
      </RouterLink>
    </form>
  </AuthPanel>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.new-password-page,
.new-password-page__mfa {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.new-password-page__mfa {
  gap: $space-3;
  padding-top: $space-4;
  border-top: 1px solid $color-border;
}

.new-password-page__subtitle {
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
}

.new-password-page__link {
  align-self: center;
  color: $color-primary;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}
</style>
