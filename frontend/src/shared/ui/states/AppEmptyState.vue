<!-- Estado vacío: dice qué pasa y ofrece una acción, nunca deja la pantalla en blanco. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
defineProps<{ title: string; description?: string }>()
</script>

<template>
  <div class="app-state">
    <p class="app-state__title">{{ title }}</p>
    <p v-if="description" class="app-state__text">{{ description }}</p>
    <slot />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: $space-2;
  padding: $space-6 $space-4;
  text-align: center;
}

.app-state__title {
  margin: 0;
  font-weight: $font-weight-medium;
}

.app-state__text {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}
</style>
