<!-- Cambiar la contraseña desde Mi cuenta: la actual (como reconfirmación), la nueva y su confirmación, con los
     requisitos a la vista, sobre AppFormDialog. Los errores se muestran sin cerrar el diálogo. Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { fieldMessagesFor, messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppFormDialog from '@/shared/ui/feedback/AppFormDialog.vue'
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
  <AppFormDialog
    v-model="open"
    title="Cambiar la contraseña"
    message="Cerraremos tus sesiones en otros equipos; esta sigue abierta."
    submit-label="Cambiar contraseña"
    :saving="saving"
    :error="error"
    @submit="submit"
  >
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
  </AppFormDialog>
</template>
