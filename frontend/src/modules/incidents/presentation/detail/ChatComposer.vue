<!-- Campo para escribir en el chat del incidente, al pie del panel: Enter envía y Shift + Enter hace un salto de
     línea. Es el único lugar donde se escribe. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { ref } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import type { useConversation } from '../../application/useConversation'
import { COMMENT_MAX } from '../../domain/incident'

const props = defineProps<{ conversation: ReturnType<typeof useConversation> }>()

const { sending } = props.conversation
const draft = ref('')
const form = ref<HTMLFormElement | null>(null)

async function send(): Promise<void> {
  const body = draft.value.trim()
  if (!body || sending.value) return
  if (await props.conversation.send(body)) draft.value = ''
}

function focus(): void {
  ;(form.value?.querySelector('textarea') as HTMLTextAreaElement | null)?.focus()
}

defineExpose({ focus })
</script>

<template>
  <form ref="form" class="chat-composer" @submit.prevent="send" @keydown.enter.exact.prevent="send">
    <AppTextField
      v-model="draft"
      label="Mensaje"
      type="textarea"
      placeholder="Escribe un mensaje para quienes atienden el incidente"
      info="Enter envía el mensaje; Shift + Enter hace un salto de línea."
      :maxlength="COMMENT_MAX"
      counter
    />
    <AppButton type="submit" icon="send" :loading="sending" :disabled="!draft.trim()">Enviar</AppButton>
  </form>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.chat-composer {
  display: flex;
  flex-direction: column;
  gap: $space-2;

  > :last-child {
    align-self: flex-end;
  }
}
</style>
