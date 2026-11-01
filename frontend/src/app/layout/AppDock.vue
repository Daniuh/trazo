<!-- Menú principal en forma de dock: flota abajo y centrado en las cuatro vistas, y a la derecha (tras una línea) lleva lo
     que llegue por el slot: la campana y la cuenta. Tablet, portátil y monitor: solo íconos, con su nombre al pasar el
     cursor o al enfocarlos. Celular: a lo ancho, con el nombre bajo cada ícono; si no caben todas las secciones, "Más"
     abre el resto en un panel. Documentado en docs/frontend/sistema-de-diseno/layout.md -->
<script setup lang="ts">
import { QTooltip, useQuasar } from 'quasar'
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppSheet from '@/shared/ui/AppSheet.vue'
import { useViewport } from '@/shared/ui/useViewport'
import type { NavItem } from '../navigation'
import { dockSlots, inSection, splitDock } from './dock'

const props = defineProps<{ items: NavItem[] }>()

const { isMobile } = useViewport()
const { screen } = useQuasar()
const route = useRoute()
const router = useRouter()
const moreOpen = ref(false)

const split = computed(() =>
  isMobile.value ? splitDock(props.items, dockSlots(screen.width)) : { shown: props.items, more: [] },
)
const isActive = (item: NavItem) => inSection(route.path, router.resolve(item.to).path, item.exact)
// "Más" se marca cuando la página actual es una de las secciones que guarda
const moreActive = computed(() => split.value.more.some(isActive))
// El nombre sale encima del ícono, hacia el contenido
const tooltipSide = { anchor: 'top middle', self: 'bottom middle', offset: [0, 10] } as const
</script>

<template>
  <div class="dock" :class="{ 'dock--mobile': isMobile }">
    <nav id="menu-principal" class="dock__nav" aria-label="Menú principal">
      <RouterLink
        v-for="item in split.shown"
        :key="item.label"
        :to="item.to"
        class="dock__item"
        :class="{ 'dock__item--active': isActive(item) }"
        :aria-current="isActive(item) ? 'page' : undefined"
        :aria-label="isMobile ? undefined : item.label"
      >
        <AppIcon :name="item.icon" />
        <span v-if="isMobile" class="dock__label">{{ item.label }}</span>
        <QTooltip v-else v-bind="tooltipSide">{{ item.label }}</QTooltip>
      </RouterLink>
      <button
        v-if="split.more.length > 0"
        class="dock__item"
        :class="{ 'dock__item--active': moreActive }"
        type="button"
        aria-haspopup="dialog"
        @click="moreOpen = true"
      >
        <AppIcon name="more" />
        <span class="dock__label">Más</span>
      </button>
    </nav>

    <template v-if="$slots.default">
      <span class="dock__divider" aria-hidden="true" />
      <div class="dock__extras"><slot /></div>
    </template>

    <AppSheet v-model:open="moreOpen" title="Más secciones">
      <nav class="dock__more" aria-label="Más secciones">
        <RouterLink
          v-for="item in split.more"
          :key="item.label"
          :to="item.to"
          class="dock__more-item"
          :class="{ 'dock__more-item--active': isActive(item) }"
          :aria-current="isActive(item) ? 'page' : undefined"
          @click="moreOpen = false"
        >
          <AppIcon :name="item.icon" />
          {{ item.label }}
        </RouterLink>
      </nav>
    </AppSheet>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Celular (mobile first): a lo ancho, separado de los bordes, con el nombre bajo cada ícono
.dock {
  position: fixed;
  z-index: $z-dock;
  right: $space-2;
  bottom: calc(#{$space-2} + env(safe-area-inset-bottom));
  left: $space-2;
  display: flex;
  align-items: center;
  gap: $space-1;
  height: $dock-height-mobile;
  padding: $dock-padding;
  border: 1px solid $color-border;
  border-radius: $radius-dock;
  background: $color-surface;
  box-shadow: $shadow-overlay;
}

// En celular, las secciones con un poco de aire entre sí y el nombre pegado al ícono
// Ocupa todo el alto del dock: así el fondo de la sección activa deja aire arriba y abajo
.dock__nav {
  display: flex;
  flex: 1;
  align-items: stretch;
  align-self: stretch;
  gap: $space-1;
  min-width: 0;
}

.dock__item {
  display: inline-flex;
  flex-direction: column;
  flex: 1;
  align-items: center;
  justify-content: center;
  gap: $dock-label-gap;
  min-width: 0;
  height: 100%;
  padding: 0 $space-1;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text-secondary;
  font: inherit;
  text-decoration: none;
  cursor: pointer;

  &:hover {
    background: $color-page;
    color: $color-text;
  }
}

.dock__label {
  max-width: 100%;
  overflow: hidden;
  font-size: $font-size-xs;
  line-height: $line-height-tight;
  text-overflow: ellipsis;
  white-space: nowrap;
}

// Activo: fondo azul oscuro de marca con ícono y texto blancos (5:1), igual que el botón principal
.dock__item--active,
.dock__item--active:hover {
  background: $color-primary;
  color: $color-on-dark;
  font-weight: $font-weight-medium;
}

.dock__divider {
  flex: none;
  width: 1px;
  height: $dock-divider-height;
  margin: 0 $space-1;
  background: $color-border;
}

.dock__extras {
  display: flex;
  align-items: center;
}

// Campana y cuenta más angostas en celular, para dejar espacio a los nombres de las secciones
.dock__extras > :deep(*) {
  width: $control-height-compact;
}

.dock__more {
  display: flex;
  flex-direction: column;
  gap: $space-1;
}

.dock__more-item {
  display: flex;
  align-items: center;
  gap: $space-3;
  min-height: $touch-target;
  padding: 0 $space-3;
  border-radius: $radius-control;
  color: $color-text;
  text-decoration: none;

  &:hover {
    background: $color-page;
  }
}

.dock__more-item--active,
.dock__more-item--active:hover {
  background: $color-primary;
  color: $color-on-dark;
  font-weight: $font-weight-medium;
}

// Tablet en adelante: una isla centrada del ancho de sus íconos, como el dock de un escritorio
@include tablet {
  .dock__nav,
  .dock__extras {
    gap: $space-1;
  }

  .dock__nav {
    align-items: center;
  }

  .dock__extras > :deep(*) {
    width: $touch-target;
  }

  .dock {
    right: auto;
    bottom: calc(#{$dock-offset} + env(safe-area-inset-bottom));
    left: 50%;
    height: $dock-height;
    transform: translateX(-50%);
  }

  .dock__nav {
    flex: none;
  }

  .dock__item {
    flex: none;
    width: $touch-target;
    height: $touch-target;
    padding: 0;
  }
}
</style>
