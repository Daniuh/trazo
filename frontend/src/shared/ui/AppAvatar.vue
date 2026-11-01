<!-- Círculo con las iniciales de una persona, en tres tamaños: sm en las filas del listado, md en el historial y el
     chat, lg en la ficha. Es decorativo: el nombre siempre va en texto al lado o en el nombre del control que lo
     contiene. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { initials } from '@/shared/format/initials'

const props = withDefaults(defineProps<{ name: string; size?: 'sm' | 'md' | 'lg' }>(), { size: 'sm' })

const letters = computed(() => initials(props.name))
</script>

<template>
  <span class="app-avatar" :class="`app-avatar--${size}`" aria-hidden="true">{{ letters }}</span>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Fondo oscuro neutro con texto blanco (10:1)
.app-avatar {
  display: inline-flex;
  flex: none;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: $color-neutral-dark;
  color: $color-on-dark;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.app-avatar--sm {
  width: $avatar-size-sm;
  height: $avatar-size-sm;
  font-size: $font-size-xs;
}

.app-avatar--md {
  width: $avatar-size;
  height: $avatar-size;
  font-size: $font-size-sm;
}

.app-avatar--lg {
  width: $avatar-size-lg;
  height: $avatar-size-lg;
  font-size: $font-size-md;
}
</style>
