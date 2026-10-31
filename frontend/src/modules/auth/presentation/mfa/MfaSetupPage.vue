<!-- Configurar la verificación en dos pasos: escanear el QR (o escribir la clave), confirmar con el primer código y
     guardar los códigos de recuperación. Tres formas de llegar:
     - obligatoria: durante el ingreso, sin poder posponerla;
     - sugerida: recién ingresó con gracia, con "Configurar más tarde";
     - voluntaria: desde Mi cuenta, con "Cancelar".
     Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { fieldMessageFor, messageFor } from '@/shared/errors'
import { ApiError, toApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCopyBlock from '@/shared/ui/AppCopyBlock.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppQrCode from '@/shared/ui/AppQrCode.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppCheckbox from '@/shared/ui/form/AppCheckbox.vue'
import AppCodeField from '@/shared/ui/form/AppCodeField.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { LOGIN_ROUTE } from '../../application/authGuard'
import { useSessionStore } from '../../application/useSessionStore'
import { safeRedirect } from '../../domain/safeRedirect'
import type { MfaSetupData } from '../../infrastructure/authApi'
import { MFA_CHALLENGE_EXPIRED } from '../../errors'
import AuthPanel from '../AuthPanel.vue'
import RecoveryCodesPanel from './RecoveryCodesPanel.vue'

type SetupMode = 'required' | 'suggested' | 'voluntary'

const route = useRoute()
const router = useRouter()
const session = useSessionStore()

const mode = computed<SetupMode | null>(() => {
  if (session.pending?.step === 'mfa_setup') return 'required'
  if (!session.isAuthenticated) return null
  return route.query.sugerido === '1' ? 'suggested' : 'voluntary'
})

const setup = ref<MfaSetupData | null>(null)
const code = ref('')
const recoveryCodes = ref<string[]>([])
const saved = ref(false)
const loadError = ref<string | null>(null)
const error = ref<string | null>(null)
const fieldError = ref<string | undefined>()
const confirming = ref(false)

// La clave en grupos de 4, más fácil de copiar a mano a la aplicación
const groupedSecret = computed(() => setup.value?.secret.match(/.{1,4}/g)?.join(' ') ?? '')
const account = computed(() => session.user?.email ?? '')

const destination = () => safeRedirect(route.query.volver) ?? { name: mode.value === 'voluntary' ? 'account' : 'home' }

function describe(failure: ApiError): string {
  return messageFor(failure.code === 'UNAUTHENTICATED' ? new ApiError('http', MFA_CHALLENGE_EXPIRED) : failure)
}

onMounted(async () => {
  if (!mode.value) {
    await router.replace({ name: LOGIN_ROUTE })
    return
  }
  try {
    setup.value = await session.startMfaSetup()
  } catch (raw) {
    loadError.value = describe(toApiError(raw))
  }
})

async function confirm(): Promise<void> {
  if (confirming.value) return
  fieldError.value = code.value.length === 6 ? undefined : fieldMessageFor({ code: 'REQUIRED' })
  if (fieldError.value) return

  confirming.value = true
  error.value = null
  try {
    recoveryCodes.value = await session.confirmMfaSetup(code.value)
  } catch (raw) {
    error.value = describe(toApiError(raw))
    code.value = ''
  } finally {
    confirming.value = false
  }
}

async function leave(): Promise<void> {
  await router.replace(destination())
}

// Cancelar durante un ingreso obligatorio significa no entrar: se vuelve al login
async function restart(): Promise<void> {
  session.clear()
  await router.replace({ name: LOGIN_ROUTE })
}
</script>

<template>
  <AuthPanel title="Verificación en dos pasos">
    <div class="mfa-setup">
      <!-- Paso final: códigos de recuperación -->
      <template v-if="recoveryCodes.length > 0">
        <AppAlert tone="info">Listo: tu cuenta ya pide el código de la aplicación al ingresar.</AppAlert>
        <RecoveryCodesPanel :codes="recoveryCodes" :account="account" />
        <AppCheckbox v-model="saved" label="Ya guardé mis códigos de recuperación" />
        <AppButton block :disabled="!saved" @click="leave">Continuar</AppButton>
      </template>

      <template v-else>
        <p class="mfa-setup__lead">
          {{
            mode === 'required'
              ? 'Tu rol exige un segundo paso para ingresar. Configúralo ahora con la aplicación de autenticación de tu teléfono.'
              : 'Protege tu cuenta con un segundo paso: además de tu contraseña, un código de la aplicación de autenticación de tu teléfono.'
          }}
        </p>

        <AppAlert v-if="loadError">{{ loadError }}</AppAlert>
        <AppSkeleton v-else-if="!setup" :lines="6" />

        <form v-else class="mfa-setup__form" novalidate @submit.prevent="confirm">
          <section class="mfa-setup__step" aria-labelledby="mfa-step-scan">
            <h2 id="mfa-step-scan" class="mfa-setup__step-title">
              1. Escanea el código
              <AppInfoTip
                text="Sirve cualquier aplicación de autenticación: Google Authenticator, Microsoft Authenticator, Authy o la que ya uses. Ábrela, elige agregar una cuenta y escanea el código."
                label="Qué aplicación usar"
              />
            </h2>
            <AppQrCode
              class="mfa-setup__qr"
              :value="setup.uri"
              label="Código QR para agregar Trazo a tu aplicación de autenticación"
            />
            <p class="mfa-setup__hint">¿No puedes escanearlo? Escribe esta clave en la aplicación:</p>
            <AppCopyBlock :value="setup.secret" :display="groupedSecret" label="la clave" />
          </section>

          <section class="mfa-setup__step" aria-labelledby="mfa-step-confirm">
            <h2 id="mfa-step-confirm" class="mfa-setup__step-title">2. Escribe el código que muestra la aplicación</h2>
            <AppAlert v-if="error">{{ error }}</AppAlert>
            <AppCodeField v-model="code" label="Código de verificación" :error="fieldError" />
          </section>

          <AppButton type="submit" :loading="confirming" block>Activar</AppButton>
        </form>

        <AppButton v-if="mode === 'suggested'" variant="link" @click="leave">Configurar más tarde</AppButton>
        <AppButton v-else-if="mode === 'voluntary'" variant="link" @click="leave">Cancelar</AppButton>
        <AppButton v-else variant="link" @click="restart">Volver a ingresar</AppButton>
      </template>
    </div>
  </AuthPanel>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.mfa-setup,
.mfa-setup__form,
.mfa-setup__step {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.mfa-setup__step {
  gap: $space-3;
}

.mfa-setup__lead,
.mfa-setup__hint {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-md;
}

.mfa-setup__step-title {
  display: flex;
  align-items: center;
  gap: $space-1;
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
}

.mfa-setup__qr {
  align-self: center;
}
</style>
