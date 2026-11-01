<!-- Contraseña nueva y su confirmación, con los requisitos a la vista que se marcan mientras se escribe.
     Los requisitos son imprescindibles para completar el paso, por eso no van en un ícono de información.
     Lo usan la recuperación y Mi cuenta. Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import { PASSWORD_MAX_LENGTH, passwordRequirements } from '../../domain/password'

defineProps<{ errors: Record<string, string>; autofocus?: boolean }>()
const password = defineModel<string>('password', { required: true })
const confirmation = defineModel<string>('confirmation', { required: true })

const requirements = computed(() => passwordRequirements(password.value, confirmation.value))
</script>

<template>
  <div class="new-password">
    <AppTextField
      v-model="password"
      label="Contraseña nueva"
      type="password"
      autocomplete="new-password"
      :maxlength="PASSWORD_MAX_LENGTH"
      :error="errors.password"
      :autofocus="autofocus"
    />
    <AppTextField
      v-model="confirmation"
      label="Repite la contraseña nueva"
      type="password"
      autocomplete="new-password"
      :maxlength="PASSWORD_MAX_LENGTH"
      :error="errors.password_confirmation"
    />

    <ul class="new-password__requirements" aria-label="Requisitos de la contraseña">
      <li
        v-for="item in requirements"
        :key="item.key"
        class="new-password__requirement"
        :class="{ 'new-password__requirement--met': item.met === true }"
      >
        <AppIcon :name="item.met === true ? 'check' : item.met === null ? 'shield' : 'circle'" size="sm" />
        <span>{{ item.label }}</span>
        <span class="new-password__state">{{
          item.met === true ? '(cumplido)' : item.met === false ? '(pendiente)' : ''
        }}</span>
      </li>
    </ul>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.new-password {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.new-password__requirements {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  margin: 0;
  padding: 0;
  list-style: none;
}

.new-password__requirement {
  display: flex;
  align-items: flex-start;
  gap: $space-2;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

.new-password__requirement--met {
  color: $color-success;
}

// El estado se lee en lectores de pantalla; a la vista lo dice el ícono y el color
.new-password__state {
  @include visually-hidden;
}
</style>
