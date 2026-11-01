<!-- Diálogo para un cambio que se explica con una nota: cambiar de estado, pedir o decidir una reapertura. Si la nota es
     opcional, el campo aparece al tocar "Agregar una nota". Los errores quedan dentro del diálogo; ante un conflicto de
     versión se cierra para que la página ofrezca recargar. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { fieldMessageFor, fieldMessagesFor, messageFor } from '@/shared/errors'
import type { ApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppFormDialog from '@/shared/ui/feedback/AppFormDialog.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import { NOTE_MAX } from '../../domain/incident'
import type { NoteRequest } from '../useNoteDialog'

const props = defineProps<{ request: NoteRequest | null; failure: ApiError | null; conflict: boolean }>()
const emit = defineEmits<{ done: [ok: boolean] }>()
const open = defineModel<boolean>({ required: true })

const note = ref('')
const expanded = ref(false)
const saving = ref(false)
const localError = ref<string | null>(null)

watch(open, (isOpen) => {
  if (isOpen) {
    note.value = ''
    expanded.value = Boolean(props.request?.required)
    localError.value = null
  } else if (!saving.value) {
    emit('done', false)
  }
})

// La API responde el campo como "note" (cambio de estado) o "reason" (reapertura)
const fieldError = computed(() => {
  if (localError.value) return localError.value
  if (!props.failure?.isValidation) return undefined
  const fields = fieldMessagesFor(props.failure)
  return fields.note ?? fields.reason
})
const generalError = computed(() => (props.failure && !props.failure.isValidation ? messageFor(props.failure) : null))

async function submit(): Promise<void> {
  const request = props.request
  if (!request || saving.value) return
  const text = note.value.trim()
  if (request.required && text === '') {
    localError.value = fieldMessageFor({ code: 'REQUIRED' })
    expanded.value = true
    return
  }

  localError.value = null
  saving.value = true
  try {
    const ok = await request.run(text === '' ? null : text)
    if (ok || props.conflict) {
      open.value = false
      emit('done', ok)
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppFormDialog
    v-if="request"
    v-model="open"
    :title="request.title"
    :message="request.message"
    :submit-label="request.submitLabel"
    :danger="request.danger"
    :saving="saving"
    :error="generalError"
    @submit="submit"
  >
    <AppTextField
      v-if="expanded"
      v-model="note"
      :label="request.label"
      type="textarea"
      :required="request.required"
      :maxlength="NOTE_MAX"
      :error="fieldError"
      autofocus
    />
    <AppButton v-else class="incident-note-dialog__add" variant="link" icon="plus" @click="expanded = true">
      Agregar una nota
    </AppButton>
  </AppFormDialog>
</template>

<style scoped lang="scss">
.incident-note-dialog__add {
  align-self: flex-start;
}
</style>
