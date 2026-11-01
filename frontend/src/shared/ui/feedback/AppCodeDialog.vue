<!-- Diálogo que pide el código actual de la aplicación antes de una acción sensible (regenerar códigos, desactivar el
     MFA o confirmar un cambio de administración), sobre AppFormDialog. Muestra el error de la API sin cerrarse, para
     corregir el código. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { ref, watch } from 'vue'
import { fieldMessageFor, messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppCodeField from '../form/AppCodeField.vue'
import AppFormDialog from './AppFormDialog.vue'

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
  <AppFormDialog
    v-model="open"
    :title="title"
    :message="message"
    :submit-label="confirmLabel"
    :danger="danger"
    :saving="working"
    :error="error"
    @submit="submit"
  >
    <AppCodeField v-model="code" label="Código de la aplicación" :error="fieldError" autofocus />
  </AppFormDialog>
</template>
