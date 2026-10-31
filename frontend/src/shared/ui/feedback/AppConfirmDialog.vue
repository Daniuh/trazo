<!-- Diálogo de confirmación: explica la consecuencia antes de una acción importante. Se abre con useConfirm(). -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QDialog, useDialogPluginComponent } from 'quasar'
import AppButton from '../AppButton.vue'

defineProps<{ title: string; message: string; confirmLabel: string; danger?: boolean }>()
defineEmits([...useDialogPluginComponent.emits])

const { dialogRef, onDialogHide, onDialogOK, onDialogCancel } = useDialogPluginComponent()

// Quasar controla el diálogo a través de esta referencia
defineExpose({ dialogRef })
</script>

<template>
  <QDialog ref="dialogRef" @hide="onDialogHide">
    <div class="confirm" role="alertdialog" aria-modal="true">
      <h2 class="confirm__title">{{ title }}</h2>
      <p class="confirm__message">{{ message }}</p>
      <div class="confirm__actions">
        <AppButton variant="secondary" @click="onDialogCancel">Cancelar</AppButton>
        <AppButton :variant="danger ? 'danger' : 'primary'" @click="onDialogOK">{{ confirmLabel }}</AppButton>
      </div>
    </div>
  </QDialog>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.confirm {
  width: min(28rem, calc(100vw - #{$space-6}));
  padding: $space-5;
  border-radius: $radius-card;
  background: $color-surface;
  box-shadow: $shadow-overlay;
}

.confirm__title {
  margin: 0 0 $space-2;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

.confirm__message {
  margin: 0 0 $space-5;
  color: $color-text-secondary;
}

// En celular los botones se apilan, con la acción principal arriba
.confirm__actions {
  display: flex;
  flex-direction: column-reverse;
  gap: $space-2;
}

@include tablet {
  .confirm__actions {
    flex-direction: row;
    justify-content: flex-end;
  }
}
</style>
