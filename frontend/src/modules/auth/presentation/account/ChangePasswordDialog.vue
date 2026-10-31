<!-- Cambiar la contraseña desde Mi cuenta: la actual (como reconfirmación), la nueva y su confirmación, con los
     requisitos a la vista. Los errores se muestran sin cerrar el diálogo. Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { QDialog } from 'quasar'
import { reactive, ref, watch } from 'vue'
import { fieldMessagesFor, messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import { useSessionStore } from '../../application/useSessionStore'
import NewPasswordFields from '../password/NewPasswordFields.vue'

const open = defineModel<boolean>({ required: true })
const emit = defineEmits<{ changed: [] }>()
const session = useSessionStore()

// Errores de la API que corresponden a un campo y no a todo el formulario
const FIELD_CODES: Record<string, string> = {
  CURRENT_PASSWORD_INVALID: 'current_password',
  PASSWORD_SAME_AS_CURRENT: 'password',
}

const form = reactive({ current: '', password: '', confirmation: '' })
const fieldErrors = ref<Record<string, string>>({})
const error = ref<string | null>(null)
const saving = ref(false)

watch(open, (isOpen) => {
  if (!isOpen) return
  Object.assign(form, { current: '', password: '', confirmation: '' })
  fieldErrors.value = {}
  error.value = null
})

async function submit(): Promise<void> {
  if (saving.value) return
  saving.value = true
  error.value = null
  fieldErrors.value = {}
  try {
    await session.changePassword(form.current, form.password, form.confirmation)
    open.value = false
    emit('changed')
  } catch (raw) {
    const failure = toApiError(raw)
    const field = FIELD_CODES[failure.code]
    if (field) fieldErrors.value = { [field]: messageFor(failure) }
    else {
      fieldErrors.value = fieldMessagesFor(failure)
      error.value = failure.isValidation ? null : messageFor(failure)
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <QDialog v-model="open">
    <!-- Quasar solo deja hacer clic en un div hijo directo del diálogo: el formulario va dentro -->
    <div>
      <form
        class="change-password"
        role="dialog"
        aria-modal="true"
        aria-label="Cambiar la contraseña"
        novalidate
        @submit.prevent="submit"
      >
        <h2 class="change-password__title">Cambiar la contraseña</h2>
        <p class="change-password__message">Cerraremos tus sesiones en otros equipos; esta sigue abierta.</p>
        <AppAlert v-if="error">{{ error }}</AppAlert>
        <AppTextField
          v-model="form.current"
          label="Contraseña actual"
          type="password"
          autocomplete="current-password"
          :maxlength="255"
          :error="fieldErrors.current_password"
          autofocus
        />
        <NewPasswordFields
          v-model:password="form.password"
          v-model:confirmation="form.confirmation"
          :errors="fieldErrors"
        />
        <div class="change-password__actions">
          <AppButton variant="secondary" :disabled="saving" @click="open = false">Cancelar</AppButton>
          <AppButton type="submit" :loading="saving">Cambiar contraseña</AppButton>
        </div>
      </form>
    </div>
  </QDialog>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.change-password {
  display: flex;
  flex-direction: column;
  gap: $space-4;
  width: min(28rem, calc(100vw - #{$space-6}));
  max-height: calc(100vh - #{$space-6});
  padding: $space-5;
  overflow-y: auto;
  border-radius: $radius-card;
  background: $color-surface;
  box-shadow: $shadow-overlay;
}

.change-password__title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

.change-password__message {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-md;
}

.change-password__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: $space-2;
}
</style>
