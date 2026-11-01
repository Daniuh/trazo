<!-- Campos de nombre y correo de una persona, con su error debajo. Los usan crear una cuenta y editar sus datos.
     Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import type { ProfileDraft } from '../../domain/user'

defineProps<{ errors: Partial<Record<keyof ProfileDraft, string>>; autofocus?: boolean }>()
const draft = defineModel<ProfileDraft>({ required: true })
</script>

<template>
  <AppTextField
    v-model="draft.name"
    label="Nombre completo"
    autocomplete="off"
    :maxlength="120"
    :error="errors.name"
    :autofocus="autofocus"
    required
  />
  <AppTextField
    v-model="draft.email"
    label="Correo"
    type="email"
    autocomplete="off"
    :maxlength="191"
    :error="errors.email"
    info="Con este correo ingresa y recibe los avisos de su cuenta."
    required
  />
</template>
