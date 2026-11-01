<!-- Tarjeta: superficie casi blanca con borde suave, título opcional y zona de acciones. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
defineProps<{ title?: string }>()
</script>

<template>
  <section class="app-card">
    <header v-if="title || $slots.actions" class="app-card__header">
      <h2 v-if="title" class="app-card__title">{{ title }}</h2>
      <div class="app-card__actions"><slot name="actions" /></div>
    </header>
    <slot />
  </section>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-card {
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.app-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
  margin-bottom: $space-3;
}

.app-card__title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.app-card__actions {
  display: flex;
  gap: $space-2;
}

@include laptop {
  .app-card {
    padding: $space-5;
  }
}
</style>
