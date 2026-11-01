<!-- Diálogo con un formulario corto: título, error general visible, los campos en el slot y los botones Cancelar y
     de guardar. No se cierra solo: quien lo usa lo cierra cuando el guardado sale bien, así los errores quedan a la
     vista. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QDialog } from 'quasar'
import AppButton from '../AppButton.vue'
import AppAlert from './AppAlert.vue'

defineProps<{
  title: string
  submitLabel: string
  // Texto bajo el título que explica la acción
  message?: string
  saving?: boolean
  error?: string | null
  // Acción que no se puede deshacer fácilmente: el botón va en rojo
  danger?: boolean
}>()
const emit = defineEmits<{ submit: [] }>()
const open = defineModel<boolean>({ required: true })
</script>

<template>
  <QDialog v-model="open">
    <!-- Quasar solo deja hacer clic en un div hijo directo del diálogo: el formulario va dentro -->
    <div>
      <form
        class="app-form-dialog"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        novalidate
        @submit.prevent="emit('submit')"
      >
        <h2 class="app-form-dialog__title">{{ title }}</h2>
        <p v-if="message" class="app-form-dialog__message">{{ message }}</p>
        <AppAlert v-if="error">{{ error }}</AppAlert>
        <slot />
        <div class="app-form-dialog__actions">
          <AppButton variant="secondary" :disabled="saving" @click="open = false">Cancelar</AppButton>
          <AppButton type="submit" :variant="danger ? 'danger' : 'primary'" :loading="saving">{{
            submitLabel
          }}</AppButton>
        </div>
      </form>
    </div>
  </QDialog>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-form-dialog {
  display: flex;
  flex-direction: column;
  gap: $space-4;
  width: min(32rem, calc(100vw - #{$space-6}));
  max-height: calc(100vh - #{$space-6});
  padding: $space-5;
  overflow-y: auto;
  border-radius: $radius-card;
  background: $color-surface;
  box-shadow: $shadow-overlay;
}

.app-form-dialog__title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;

  // Quasar le da a los h2 un alto de línea de título grande
  line-height: $line-height-tight;
}

.app-form-dialog__message {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-md;
}

// En celular los botones se apilan, con la acción principal arriba
.app-form-dialog__actions {
  display: flex;
  flex-direction: column-reverse;
  gap: $space-2;
}

@include tablet {
  .app-form-dialog__actions {
    flex-direction: row;
    justify-content: flex-end;
  }
}
</style>
