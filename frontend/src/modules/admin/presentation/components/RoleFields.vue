<!-- Datos y política de MFA de un rol: código (solo al crearlo), nombre, nivel, si la verificación en dos pasos es
     obligatoria y cuántos ingresos se puede posponer. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppCheckbox from '@/shared/ui/form/AppCheckbox.vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import { MAX_GRACE_LOGINS, type RoleDraft } from '../../domain/role'

defineProps<{ creating: boolean; errors: Partial<Record<keyof RoleDraft, string>> }>()
const draft = defineModel<RoleDraft>({ required: true })

const graceOptions = Array.from({ length: MAX_GRACE_LOGINS + 1 }, (_, count) => ({
  value: count,
  label:
    count === 0 ? 'Ninguno: la configura en su primer ingreso' : `${count} ${count === 1 ? 'ingreso' : 'ingresos'}`,
}))

const grace = computed({
  get: () => draft.value.mfa.graceLogins,
  set: (value: number | number[] | null) =>
    (draft.value = { ...draft.value, mfa: { ...draft.value.mfa, graceLogins: Number(value ?? 0) } }),
})
const required = computed({
  get: () => draft.value.mfa.required,
  set: (value: boolean) => (draft.value = { ...draft.value, mfa: { ...draft.value.mfa, required: value } }),
})
</script>

<template>
  <AppCard title="Datos">
    <div class="role-fields">
      <AppTextField
        v-if="creating"
        v-model="draft.code"
        label="Código"
        :maxlength="40"
        :error="errors.code"
        info="Identificador estable del rol para integraciones. Letras minúsculas sin tildes, números y guion bajo, empezando por una letra (p. ej. soporte_n2). No se cambia después."
        autocomplete="off"
        required
      />
      <AppTextField v-model="draft.name" label="Nombre" :maxlength="80" :error="errors.name" required />
      <AppTextField
        v-model="draft.level"
        label="Nivel"
        inputmode="numeric"
        :maxlength="2"
        :error="errors.level"
        info="Un número del 1 al 99 que ningún otro rol tenga. Cada persona solo administra a quienes tienen un rol de menor nivel. Los roles base van de 20 en 20, así que hay espacio para intercalar."
        required
      />
    </div>
  </AppCard>

  <AppCard title="Verificación en dos pasos">
    <div class="role-fields">
      <AppCheckbox v-model="required" label="Obligatoria para este rol" />
      <AppSelect
        v-if="draft.mfa.required"
        v-model="grace"
        label="Ingresos en que se puede posponer"
        :options="graceOptions"
      />
    </div>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.role-fields {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}
</style>
