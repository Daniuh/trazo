<!-- Marco de cada widget: tarjeta con título, la explicación en el ícono de información y el contenido.
     Mientras no llegan sus datos muestra un esqueleto. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import AppCard from '@/shared/ui/AppCard.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'

defineProps<{ title: string; description: string; ready: boolean }>()
</script>

<template>
  <AppCard :title="title" class="widget-card">
    <template #actions>
      <slot name="actions" />
      <AppInfoTip :text="description" :label="`Qué muestra ${title}`" />
    </template>
    <div class="widget-card__body">
      <slot v-if="ready" />
      <AppSkeleton v-else :lines="3" />
    </div>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Más compacta que la tarjeta general: el dashboard reúne muchas en una pantalla
.widget-card {
  display: flex;
  flex-direction: column;
  min-width: 0;
  height: 100%;
  padding: $space-4;
}

.widget-card :deep(.app-card__header) {
  margin-bottom: $space-2;
}

.widget-card :deep(.app-card__title) {
  font-size: $font-size-md;
}

@include laptop {
  .widget-card {
    padding: $space-4;
  }
}

.widget-card__body {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
  overflow: auto;
}

// El contenido ocupa todo el alto de la tarjeta: si su vecina de fila es más alta, el gráfico o la lista se reparten
// ese alto en lugar de dejar un hueco abajo
.widget-card__body > :slotted(*) {
  flex: 1 1 auto;
}
</style>
