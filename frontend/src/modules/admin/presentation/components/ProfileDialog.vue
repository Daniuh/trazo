<!-- Diálogo para corregir el nombre y el correo de una persona (sobre AppFormDialog). Los errores, como un correo en
     uso, se muestran en su campo sin cerrar el diálogo. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { ref, watch } from 'vue'
import { fieldMessagesFor, messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppFormDialog from '@/shared/ui/feedback/AppFormDialog.vue'
import { profileErrors } from '../../application/profileRules'
import type { ProfileDraft } from '../../domain/user'
import UserProfileFields from './UserProfileFields.vue'

const props = defineProps<{ current: ProfileDraft; action: (draft: ProfileDraft) => Promise<void> }>()
const open = defineModel<boolean>({ required: true })

const draft = ref<ProfileDraft>({ ...props.current })
const errors = ref<Partial<Record<keyof ProfileDraft, string>>>({})
const error = ref<string | null>(null)
const saving = ref(false)

watch(open, (isOpen) => {
  if (!isOpen) return
  draft.value = { ...props.current }
  errors.value = {}
  error.value = null
})

async function save(): Promise<void> {
  if (saving.value) return
  errors.value = profileErrors(draft.value)
  if (Object.keys(errors.value).length > 0) return

  saving.value = true
  error.value = null
  try {
    await props.action({ name: draft.value.name.trim(), email: draft.value.email.trim() })
    open.value = false
  } catch (raw) {
    const failure = toApiError(raw)
    errors.value = fieldMessagesFor(failure)
    if (Object.keys(errors.value).length === 0) error.value = messageFor(failure)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppFormDialog
    v-model="open"
    title="Editar datos"
    submit-label="Guardar"
    :saving="saving"
    :error="error"
    @submit="save"
  >
    <UserProfileFields v-model="draft" :errors="errors" autofocus />
  </AppFormDialog>
</template>
