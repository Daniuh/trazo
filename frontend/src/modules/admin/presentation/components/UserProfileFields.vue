<!-- Campos de nombres, apellidos y correo de una persona, con su error debajo. Nombres y apellidos van aparte para
     ordenar por apellido y entregar los datos a otros sistemas sin adivinar (ADR-0086). Los usan crear una cuenta y
     editar sus datos. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import { NAME_PART_MAX_LENGTH } from '../../application/profileRules'
import type { ProfileDraft } from '../../domain/user'

defineProps<{ errors: Partial<Record<keyof ProfileDraft, string>>; autofocus?: boolean }>()
const draft = defineModel<ProfileDraft>({ required: true })
</script>

<template>
  <div class="profile-fields__names">
    <AppTextField
      v-model="draft.givenNames"
      label="Nombres"
      autocomplete="off"
      :maxlength="NAME_PART_MAX_LENGTH"
      :error="errors.givenNames"
      :autofocus="autofocus"
      info="Uno o varios, como se escriben: María José."
      required
    />
    <AppTextField
      v-model="draft.familyNames"
      label="Apellidos"
      autocomplete="off"
      :maxlength="NAME_PART_MAX_LENGTH"
      :error="errors.familyNames"
      info="Uno o varios, como se escriben: De la Torre Gómez."
      required
    />
  </div>
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

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Uno debajo del otro en celular; lado a lado desde tablet
.profile-fields__names {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

@include tablet {
  .profile-fields__names {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  }
}
</style>
