<!-- Diálogo que pide el código actual de la aplicación antes de una acción sensible (regenerar códigos o desactivar
     el MFA). Muestra el error de la API sin cerrarse, para corregir el código. Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { QDialog } from 'quasar'
import { ref, watch } from 'vue'
import { fieldMessageFor, messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppCodeField from '@/shared/ui/form/AppCodeField.vue'

const props = defineProps<{
  title: string
  message: string
  confirmLabel: string
  danger?: boolean
  action: (code: string) => Promise<void>
}>()
const open = defineModel<boolean>({ required: true })

const code = ref('')
const error = ref<string | null>(null)
const fieldError = ref<string | undefined>()
const working = ref(false)

watch(open, (isOpen) => {
  if (!isOpen) return
  code.value = ''
  error.value = null
  fieldError.value = undefined
})

async function submit(): Promise<void> {
  if (working.value) return
  fieldError.value = code.value.length === 6 ? undefined : fieldMessageFor({ code: 'REQUIRED' })
  if (fieldError.value) return

  working.value = true
  error.value = null
  try {
    await props.action(code.value)
    open.value = false
  } catch (raw) {
    error.value = messageFor(toApiError(raw))
    code.value = ''
  } finally {
    working.value = false
  }
}
</script>

<template>
  <QDialog v-model="open">
    <!-- Quasar solo deja hacer clic en un div hijo directo del diálogo: el formulario va dentro -->
    <div>
      <form
        class="mfa-code-dialog"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        novalidate
        @submit.prevent="submit"
      >
        <h2 class="mfa-code-dialog__title">{{ title }}</h2>
        <p class="mfa-code-dialog__message">{{ message }}</p>
        <AppAlert v-if="error">{{ error }}</AppAlert>
        <AppCodeField v-model="code" label="Código de la aplicación" :error="fieldError" autofocus />
        <div class="mfa-code-dialog__actions">
          <AppButton variant="secondary" :disabled="working" @click="open = false">Cancelar</AppButton>
          <AppButton type="submit" :variant="danger ? 'danger' : 'primary'" :loading="working">{{
            confirmLabel
          }}</AppButton>
        </div>
      </form>
    </div>
  </QDialog>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.mfa-code-dialog {
  display: flex;
  flex-direction: column;
  gap: $space-4;
  width: min(28rem, calc(100vw - #{$space-6}));
  padding: $space-5;
  border-radius: $radius-card;
  background: $color-surface;
  box-shadow: $shadow-overlay;
}

.mfa-code-dialog__title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

.mfa-code-dialog__message {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-md;
}

.mfa-code-dialog__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: $space-2;
}
</style>
