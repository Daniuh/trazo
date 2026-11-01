<!-- Diálogo para cambiar el rol de una persona (sobre AppFormDialog): solo los roles que quien administra puede dar.
     Si la persona tiene permisos personalizados, avisa que el rol nuevo los reemplaza. Si la API pide el código de
     quien administra, lo pide la acción que recibe; si se cancela, el diálogo sigue abierto.
     Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { messageFor } from '@/shared/errors'
import { toApiError } from '@/shared/http'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppFormDialog from '@/shared/ui/feedback/AppFormDialog.vue'
import type { RoleOption } from '../../domain/user'
import RoleChoice from './RoleChoice.vue'

const props = defineProps<{
  current: string | null
  roles: RoleOption[]
  extraPermissions: number
  // true si se guardó; false si se canceló al pedir el código
  action: (role: string) => Promise<boolean>
}>()
const open = defineModel<boolean>({ required: true })

const role = ref<string | null>(props.current)

// Al cambiar de rol el back borra los permisos personalizados: se avisa antes de guardar
const clearNotice = computed(() => {
  if (props.extraPermissions === 0 || role.value === props.current) return ''
  const what =
    props.extraPermissions === 1
      ? 'se quita su cambio personalizado'
      : `se quitan sus ${props.extraPermissions} cambios personalizados`
  return `Con el rol nuevo, la persona queda con los permisos exactos de ese rol: ${what}.`
})
const error = ref<string | null>(null)
const saving = ref(false)

watch(open, (isOpen) => {
  if (!isOpen) return
  role.value = props.current
  error.value = null
})

async function save(): Promise<void> {
  if (saving.value || !role.value) return
  if (role.value === props.current) {
    open.value = false
    return
  }

  saving.value = true
  error.value = null
  try {
    if (await props.action(role.value)) open.value = false
  } catch (raw) {
    error.value = messageFor(toApiError(raw))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppFormDialog
    v-model="open"
    title="Cambiar rol"
    submit-label="Guardar rol"
    :saving="saving"
    :error="error"
    @submit="save"
  >
    <RoleChoice v-model="role" :roles="roles" />
    <AppAlert v-if="clearNotice" tone="info">{{ clearNotice }}</AppAlert>
  </AppFormDialog>
</template>
