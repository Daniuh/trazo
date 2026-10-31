<!-- Mi cuenta: datos de la persona, cambiar la contraseña y su verificación en dos pasos (activarla, regenerar códigos de recuperación y
     desactivarla si su rol no la exige). Una columna en celular y tablet; dos en portátil y monitor.
     Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import { useSessionStore } from '../../application/useSessionStore'
import RecoveryCodesPanel from '../mfa/RecoveryCodesPanel.vue'
import ChangePasswordDialog from './ChangePasswordDialog.vue'
import AppCodeDialog from '@/shared/ui/feedback/AppCodeDialog.vue'

const session = useSessionStore()
const router = useRouter()
const { notify } = useFeedback()

const user = computed(() => session.user)
const roles = computed(() => user.value?.roles.map((role) => role.name).join(', ') ?? '')
const mfa = computed(() => user.value?.mfa)

const regenerating = ref(false)
const changingPassword = ref(false)
const disabling = ref(false)
const newCodes = ref<string[]>([])

async function regenerate(code: string): Promise<void> {
  newCodes.value = await session.regenerateRecoveryCodes(code)
}

async function disable(code: string): Promise<void> {
  await session.disableMfa(code)
  notify('Desactivaste la verificación en dos pasos.')
}

function setUp(): void {
  void router.push({ name: 'mfa-setup', query: { volver: '/mi-cuenta' } })
}
</script>

<template>
  <div v-if="user && mfa" class="account">
    <h1 class="account__title">Mi cuenta</h1>

    <div class="account__grid">
      <div class="account__column">
        <AppCard title="Tus datos">
          <dl class="account__data">
            <dt>Nombre</dt>
            <dd>{{ user.name }}</dd>
            <dt>Correo</dt>
            <dd>{{ user.email }}</dd>
            <dt>Rol</dt>
            <dd>{{ roles }}</dd>
          </dl>
        </AppCard>

        <AppCard title="Contraseña">
          <div class="account__mfa">
            <p class="account__detail">Al cambiarla cerramos tus sesiones en otros equipos.</p>
            <div class="account__actions">
              <AppButton variant="secondary" icon="lock" @click="changingPassword = true">Cambiar contraseña</AppButton>
            </div>
          </div>
        </AppCard>
      </div>

      <AppCard title="Verificación en dos pasos">
        <template #actions>
          <AppInfoTip
            text="Además de tu contraseña, Trazo te pide el código de 6 dígitos de la aplicación de autenticación de tu teléfono. Si alguien conoce tu contraseña, igual no puede entrar."
            label="Qué es la verificación en dos pasos"
          />
        </template>

        <div class="account__mfa">
          <p class="account__status" :class="{ 'account__status--on': mfa.enabled }">
            <AppIcon :name="mfa.enabled ? 'check' : 'shield'" />
            <span>{{ mfa.enabled ? 'Activa' : 'No está activa' }}</span>
            <AppInfoTip
              v-if="mfa.enabled && mfa.required"
              text="Tu rol exige la verificación en dos pasos, por eso no se puede desactivar."
              label="Por qué no se puede desactivar"
            />
          </p>

          <template v-if="mfa.enabled">
            <p class="account__detail">
              {{
                mfa.recoveryCodesLeft === 1
                  ? 'Te queda 1 código de recuperación.'
                  : `Te quedan ${mfa.recoveryCodesLeft} códigos de recuperación.`
              }}
            </p>
            <AppAlert v-if="mfa.recoveryCodesLeft <= 2" tone="info">
              Te quedan pocos códigos de recuperación. Genera unos nuevos para no quedarte sin forma de entrar si
              pierdes tu teléfono.
            </AppAlert>

            <RecoveryCodesPanel v-if="newCodes.length > 0" :codes="newCodes" :account="user.email" />

            <div class="account__actions">
              <AppButton variant="secondary" icon="refresh" @click="regenerating = true">
                Generar códigos nuevos
              </AppButton>
              <AppButton v-if="!mfa.required" variant="danger" @click="disabling = true">Desactivar</AppButton>
            </div>
          </template>

          <template v-else>
            <p class="account__detail">
              {{
                mfa.required
                  ? 'Tu rol la exige: la próxima vez que ingreses tendrás que configurarla.'
                  : 'Actívala para proteger tu cuenta aunque alguien conozca tu contraseña.'
              }}
            </p>
            <div class="account__actions">
              <AppButton icon="shield" @click="setUp">Configurar</AppButton>
            </div>
          </template>
        </div>
      </AppCard>
    </div>

    <ChangePasswordDialog
      v-model="changingPassword"
      @changed="notify('Tu contraseña cambió. Cerramos tus sesiones en otros equipos.')"
    />
    <AppCodeDialog
      v-model="regenerating"
      title="Generar códigos nuevos"
      message="Los códigos de recuperación que tienes dejarán de servir. Escribe el código que muestra ahora tu aplicación."
      confirm-label="Generar"
      :action="regenerate"
    />
    <AppCodeDialog
      v-model="disabling"
      title="Desactivar la verificación en dos pasos"
      message="Tu cuenta volverá a pedir solo la contraseña. Escribe el código que muestra ahora tu aplicación para confirmar."
      confirm-label="Desactivar"
      danger
      :action="disable"
    />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.account {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.account__title {
  margin: 0;
  font-size: $font-size-2xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.account__grid,
.account__column {
  display: grid;
  gap: $space-4;
  align-content: start;
}

.account__data {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: $space-2 $space-4;
  margin: 0;
  font-size: $font-size-md;

  dt {
    color: $color-text-secondary;
  }

  dd {
    margin: 0;
    overflow-wrap: anywhere;
  }
}

.account__mfa {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

.account__status {
  display: flex;
  align-items: center;
  gap: $space-2;
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

.account__status--on {
  color: $color-success;
}

.account__detail {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-md;
}

.account__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
}

@include laptop {
  .account__grid {
    grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
    align-items: start;
  }
}
</style>
