<!-- Logo completo: "Traz" en Inter y el aro como la "o". El tamaño se hereda del font-size del contenedor. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/marca.md -->
<script setup lang="ts">
import AppLogoMark from './AppLogoMark.vue'

withDefaults(defineProps<{ spinning?: boolean }>(), { spinning: false })
</script>

<template>
  <span class="logo" role="img" aria-label="Trazo">
    <span aria-hidden="true">Traz</span>
    <AppLogoMark class="logo__mark" :spinning="spinning" />
  </span>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.logo {
  display: inline-flex;
  align-items: center;
  color: $color-text;
  font-family: $font-family;
  font-weight: $font-weight-semibold;
  letter-spacing: -0.02em;
  line-height: 1;
}

// La "o" mide como una minúscula y se apoya sobre la línea base
.logo__mark {
  width: 0.68em;
  height: 0.68em;
  margin-left: 0.04em;
  transform: translateY(0.06em);
}
</style>
