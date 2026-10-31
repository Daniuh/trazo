<!-- Tarjeta de un componente del panel de salud: nombre (con su explicación en el ícono de información), estado como
     etiqueta, la medición en palabras y, abajo, las acciones que le corresponden. Documentado en docs/frontend/system.md -->
<script setup lang="ts">
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import type { Tone } from '@/shared/ui/tones'

defineProps<{ title: string; help?: string; status: string; tone: Tone; details: string[] }>()
</script>

<template>
  <article class="health-card" :aria-label="`${title}: ${status}`">
    <header class="health-card__header">
      <h2 class="health-card__title">
        {{ title }}
        <AppInfoTip v-if="help" :text="help" :label="`Qué es ${title}`" />
      </h2>
      <AppBadge :tone="tone">{{ status }}</AppBadge>
    </header>
    <p v-for="line in details" :key="line" class="health-card__detail">{{ line }}</p>
    <div v-if="$slots.default" class="health-card__actions"><slot /></div>
  </article>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.health-card {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.health-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: $space-2;
}

.health-card__title {
  display: flex;
  align-items: center;
  gap: $space-1;
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.health-card__detail {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

// Las acciones quedan abajo aunque la tarjeta crezca para igualar a las vecinas
.health-card__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
  margin-top: auto;
  padding-top: $space-2;
}
</style>
