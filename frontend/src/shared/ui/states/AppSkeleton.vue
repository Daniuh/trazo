<!-- Esqueleto de carga: anticipa la forma del contenido en lugar de un spinner vacío. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
withDefaults(defineProps<{ lines?: number }>(), { lines: 3 })
</script>

<template>
  <div class="app-skeleton" role="status" aria-label="Cargando">
    <span v-for="line in lines" :key="line" class="app-skeleton__line" />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-skeleton {
  display: flex;
  flex-direction: column;
  gap: $space-2;
}

.app-skeleton__line {
  height: 0.75rem;
  border-radius: $radius-control;
  background: $color-border;
  animation: skeleton-pulse 1.4s ease-in-out infinite;

  &:first-child {
    width: 40%;
  }

  &:last-child:not(:first-child) {
    width: 70%;
  }
}

@keyframes skeleton-pulse {
  50% {
    opacity: 0.5;
  }
}
</style>
