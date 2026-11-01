<!-- Aviso dentro de la página, siempre visible: errores de un formulario o información imprescindible. -->
<!-- Fondo de superficie y borde fino de color (sin franja gruesa): nunca fondo tintado con texto del mismo color.
     Ícono, texto y acción (p. ej. "Recargar") van centrados en la misma línea. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
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
  align-items: center;
  gap: $space-2;
  padding: $space-3;
  border: 1px solid;
  border-radius: $radius-card;
  background: $color-surface;
  color: $color-text;
  font-size: $font-size-sm;
  line-height: $line-height-base;
}

.app-alert__icon {
  flex-shrink: 0;
}

// Si el aviso trae una acción, queda alineada con el texto y baja a otra línea cuando no cabe
.app-alert__text {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-1 $space-3;
  margin: 0;
}

// El botón tipo enlace trae relleno propio: se compensa para que, al bajar de línea, empiece donde empieza el texto
.app-alert__text :deep(.app-button--link) {
  margin-inline-start: -$space-2;
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
