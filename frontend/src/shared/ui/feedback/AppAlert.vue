<!-- Aviso dentro de la página, siempre visible: errores de un formulario o información imprescindible. -->
<!-- Fondo de superficie y borde de color: nunca fondo tintado con texto del mismo color. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import AppIcon from '../AppIcon.vue'

withDefaults(defineProps<{ tone?: 'danger' | 'info' }>(), { tone: 'danger' })
</script>

<template>
  <div class="app-alert" :class="`app-alert--${tone}`" :role="tone === 'danger' ? 'alert' : 'status'">
    <AppIcon class="app-alert__icon" :name="tone === 'danger' ? 'alert' : 'info'" />
    <p class="app-alert__text"><slot /></p>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-alert {
  display: flex;
  align-items: flex-start;
  gap: $space-2;
  padding: $space-3;
  border: 1px solid;
  border-left-width: 3px;
  background: $color-surface;
  color: $color-text;
  font-size: $font-size-sm;
  line-height: $line-height-base;
}

.app-alert__icon {
  flex-shrink: 0;
}

.app-alert__text {
  margin: 0;
}

.app-alert--danger {
  border-color: $color-danger;

  .app-alert__icon {
    color: $color-danger;
  }
}

.app-alert--info {
  border-color: $color-primary;

  .app-alert__icon {
    color: $color-primary;
  }
}
</style>
