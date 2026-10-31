<!-- Envoltura común de todo campo: etiqueta arriba (o al lado, con "inline", para barras de una fila), ayuda en el ícono
     de información y error siempre visible. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import AppInfoTip from '../AppInfoTip.vue'

defineProps<{ id: string; label: string; info?: string; error?: string; required?: boolean; inline?: boolean }>()
</script>

<template>
  <div class="app-field" :class="{ 'app-field--invalid': error, 'app-field--inline': inline }">
    <div class="app-field__label">
      <label :for="id">{{ label }}<span v-if="required" class="app-field__required" aria-hidden="true">*</span></label>
      <AppInfoTip v-if="info" :text="info" :label="`Más información sobre ${label}`" />
    </div>
    <slot />
    <p v-if="error" :id="`${id}-error`" class="app-field__error" role="alert">{{ error }}</p>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-field {
  display: flex;
  flex-direction: column;
  gap: $space-1;
}

.app-field__label {
  display: flex;
  align-items: center;
  gap: $space-2;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

// Etiqueta a la izquierda del control, en la misma fila: para barras donde la etiqueta arriba ocupa demasiado
.app-field--inline {
  flex-flow: row wrap;
  align-items: center;
  gap: $space-2;

  .app-field__label {
    white-space: nowrap;
  }

  > :deep(.q-field) {
    flex: 1;
    min-width: 0;
  }
}

.app-field__required {
  margin-left: 0.125rem;
  color: $color-danger;
}

.app-field__error {
  margin: 0;
  color: $color-danger;
  font-size: $font-size-xs;
}

// Los controles de Quasar toman el fondo suave y el borde rojo cuando hay error
.app-field :deep(.q-field__control) {
  background: $color-surface;
}

.app-field--invalid :deep(.q-field__control::before) {
  border-color: $color-danger;
}
</style>
