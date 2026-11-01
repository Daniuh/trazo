<!-- Marco de las pantallas sin sesión (login, MFA, recuperación, sin conexión): fondo de marca, logo y título centrados.
     El fondo junta los colores de Psicol en halos muy suaves, una trama de puntos y el aro del logo en trazo fino, que se
     mueven despacio (se detienen con "reducir movimiento"). La tarjeta queda centrada en las cuatro vistas.
     Documentado en docs/frontend/auth.md -->
<script setup lang="ts">
import { useId } from 'vue'
import AppLogo from '@/shared/ui/brand/AppLogo.vue'
import AppLogoMark from '@/shared/ui/brand/AppLogoMark.vue'

defineProps<{ title: string }>()
const titleId = useId()
</script>

<template>
  <main class="auth-panel">
    <div class="auth-panel__backdrop" aria-hidden="true">
      <span class="auth-panel__aura auth-panel__aura--blue" />
      <span class="auth-panel__aura auth-panel__aura--green" />
      <span class="auth-panel__aura auth-panel__aura--orange" />
      <span class="auth-panel__dots" />
      <AppLogoMark class="auth-panel__ring" weight="hairline" />
    </div>

    <section class="auth-panel__card" :aria-labelledby="titleId">
      <div class="auth-panel__brand"><AppLogo /></div>
      <h1 :id="titleId" class="auth-panel__title">{{ title }}</h1>
      <slot />
    </section>
  </main>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Celular primero: la tarjeta centrada en alto y ancho, con margen para que el fondo la enmarque
.auth-panel {
  position: relative;
  isolation: isolate;
  display: grid;
  place-items: center;
  min-height: 100vh;
  min-height: 100dvh;
  padding: $space-5 $space-4;
  background: $color-page;
}

// El fondo queda fijo a la pantalla: si el contenido es más alto que ella, se desplaza la tarjeta y no el fondo
.auth-panel__backdrop {
  position: fixed;
  z-index: -1;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
}

.auth-panel__aura {
  position: absolute;
  border-radius: 50%;
  animation: auth-drift $duration-ambient ease-in-out infinite alternate;
}

.auth-panel__aura--blue {
  top: -35vmax;
  left: -30vmax;
  width: 90vmax;
  height: 90vmax;
  background: radial-gradient(circle, $auth-aura-blue 0%, transparent 68%);
}

.auth-panel__aura--green {
  right: -32vmax;
  bottom: -38vmax;
  width: 85vmax;
  height: 85vmax;
  background: radial-gradient(circle, $auth-aura-green 0%, transparent 68%);
  animation-delay: calc($duration-ambient / -2);
}

.auth-panel__aura--orange {
  right: -18vmax;
  top: -14vmax;
  width: 48vmax;
  height: 48vmax;
  background: radial-gradient(circle, $auth-aura-orange 0%, transparent 68%);
  animation-duration: calc($duration-ambient * 1.4);
}

// Trama de puntos que se desvanece hacia los bordes: da textura sin competir con el contenido
.auth-panel__dots {
  position: absolute;
  inset: 0;
  background-image: radial-gradient($auth-dots 1px, transparent 1.5px);
  background-size: $space-5 $space-5;
  mask-image: radial-gradient(ellipse at center, $auth-fade-mask 20%, transparent 72%);
}

// El aro del logo, grande y en trazo fino, asomando desde la esquina inferior derecha
.auth-panel__ring {
  position: absolute;
  right: -42vmin;
  bottom: -46vmin;
  width: 120vmin;
  height: 120vmin;
  opacity: 0.45;
  animation: auth-spin $duration-ambient-ring linear infinite;
}

.auth-panel__card {
  width: 100%;
  max-width: $auth-card-width;
  padding: $space-6 $space-5;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $auth-card-surface;
  box-shadow: $shadow-auth-card;
  backdrop-filter: blur($space-3);
}

.auth-panel__brand {
  display: flex;
  justify-content: center;
  font-size: $font-size-brand;
}

.auth-panel__title {
  margin: $space-5 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
  text-align: center;
}

@include tablet {
  .auth-panel {
    padding: $space-6;
  }

  .auth-panel__card {
    padding: $space-7 $space-6;
  }

  .auth-panel__ring {
    right: -22vmin;
    bottom: -30vmin;
    width: 85vmin;
    height: 85vmin;
  }
}

// En pantallas anchas el aro se queda a la derecha, lejos de la tarjeta
@include laptop {
  .auth-panel__ring {
    right: -10vmin;
    bottom: -24vmin;
    width: 72vmin;
    height: 72vmin;
  }
}

@keyframes auth-drift {
  to {
    transform: translate3d(6%, 4%, 0) scale(1.08);
  }
}

@keyframes auth-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
