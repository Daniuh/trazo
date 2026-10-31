<!-- Barra del modo Personalizar: queda fija arriba al bajar por la página para tener siempre a mano
     "Agregar widget", "Restaurar original", "Cancelar" y "Guardar". Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import AppButton from '@/shared/ui/AppButton.vue'
import type { DashboardWidget, WidgetCode } from '../../domain/dashboard'
import AddWidgetMenu from './AddWidgetMenu.vue'

defineProps<{ hidden: DashboardWidget[]; dirty: boolean; saving: boolean; restoring: boolean }>()
const emit = defineEmits<{ add: [code: WidgetCode]; restore: []; cancel: []; save: [] }>()
</script>

<template>
  <div class="edit-toolbar">
    <h1 class="edit-toolbar__title">Personalizar inicio</h1>
    <div class="edit-toolbar__actions">
      <AddWidgetMenu :widgets="hidden" @add="emit('add', $event)" />
      <AppButton variant="secondary" :loading="restoring" :disabled="saving" @click="emit('restore')">
        Restaurar original
      </AppButton>
      <AppButton variant="secondary" :disabled="saving || restoring" @click="emit('cancel')">Cancelar</AppButton>
      <AppButton icon="check" :loading="saving" :disabled="!dirty || restoring" @click="emit('save')">
        Guardar
      </AppButton>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Fija bajo el encabezado de la app ($header-height), con el fondo de la página para que el contenido pase por debajo
.edit-toolbar {
  position: sticky;
  top: $header-height;
  z-index: 4;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
  margin: (-$space-2) 0 0;
  padding: $space-2 0;
  border-bottom: 1px solid $color-border;
  background: $color-page;
}

.edit-toolbar__title {
  margin: 0;
  font-size: $font-size-2xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.edit-toolbar__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
}
</style>
