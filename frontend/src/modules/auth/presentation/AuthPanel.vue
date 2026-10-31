<!-- Marco de las pantallas sin sesión (login, sin conexión): logo, título y contenido.
     Celular: a ancho completo. Tablet en adelante: tarjeta centrada de 400 px. Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { useId } from 'vue'
import AppLogo from '@/shared/ui/brand/AppLogo.vue'

defineProps<{ title: string }>()
const titleId = useId()
</script>

<template>
  <main class="auth-panel">
    <section class="auth-panel__card" :aria-labelledby="titleId">
      <div class="auth-panel__brand"><AppLogo /></div>
      <h1 :id="titleId" class="auth-panel__title">{{ title }}</h1>
      <slot />
    </section>
  </main>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.auth-panel {
  min-height: 100vh;
  padding: $space-6 $space-4;
  background: $color-page;
}

.auth-panel__brand {
  font-size: $font-size-2xl;
}

.auth-panel__title {
  margin: $space-5 0 $space-4;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

@include tablet {
  .auth-panel {
    display: grid;
    place-items: center;
    padding: $space-6;
  }

  .auth-panel__card {
    width: 100%;
    max-width: $auth-card-width;
    padding: $space-6;
    border: 1px solid $color-border;
    border-radius: $radius-card;
    background: $color-surface;
  }

  .auth-panel__brand {
    text-align: center;
  }
}
</style>
