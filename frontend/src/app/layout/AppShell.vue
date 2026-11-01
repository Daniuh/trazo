<!-- Estructura de la aplicación: encabezado, menú lateral y contenido, adaptados a las cuatro vistas. -->
<!-- Celular: menú hamburguesa. Tablet: menú de íconos. Portátil y monitor: menú expandido. El menú muestra solo lo permitido. Documentado en docs/frontend/sistema-de-diseno/layout.md -->
<script setup lang="ts">
import { QDrawer, QHeader, QLayout, QPageContainer } from 'quasar'
import { computed, ref } from 'vue'
import { layout } from '@/design/tokens/layout'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { useRealtimeConnection } from '@/modules/realtime/application/useRealtimeConnection'
import { useRealtimeNotices } from '@/modules/realtime/presentation/useRealtimeNotices'
import { maintenanceNotice } from '@/shared/http'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppLogo from '@/shared/ui/brand/AppLogo.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { visibleNavigation } from '../navigation'
import AppNavigation from './AppNavigation.vue'
import AppUserMenu from './AppUserMenu.vue'
import { useRouteLoading } from './useRouteLoading'

const { isMobile, isTablet } = useViewport()
const { loading } = useRouteLoading()
const drawerOpen = ref(false)
const session = useSessionStore()
const navigation = computed(() => visibleNavigation(session.can))

// El shell solo existe con sesión: aquí vive la conexión de tiempo real y los avisos personales
useRealtimeConnection()
useRealtimeNotices()

// En celular el menú se cierra al elegir una sección
const closeOnMobile = () => {
  if (isMobile.value) drawerOpen.value = false
}
</script>

<template>
  <QLayout view="hHh LpR fFf">
    <QHeader class="shell-header">
      <div class="shell-header__bar">
        <button
          v-if="isMobile"
          class="shell-header__menu"
          type="button"
          aria-label="Abrir menú"
          :aria-expanded="drawerOpen"
          @click="drawerOpen = !drawerOpen"
        >
          <AppIcon name="menu" size="lg" />
        </button>
        <RouterLink to="/" class="shell-header__brand"><AppLogo /></RouterLink>
        <AppUserMenu />
      </div>
    </QHeader>

    <QDrawer
      v-model="drawerOpen"
      class="shell-drawer"
      :width="layout.sidebarWidth"
      :mini-width="layout.sidebarMiniWidth"
      :mini="isTablet"
      :breakpoint="layout.mobileMaxWidth"
      show-if-above
      bordered
    >
      <AppNavigation :items="navigation" :compact="isTablet" @navigate="closeOnMobile" />
    </QDrawer>

    <QPageContainer>
      <main class="shell-content">
        <div v-if="loading" class="shell-loading" role="status">
          <AppLogo spinning />
          <span class="shell-loading__label">Cargando</span>
        </div>
        <!-- Solo lo ve quien sigue trabajando durante el mantenimiento; a los demás la API les responde 503 -->
        <AppAlert v-if="maintenanceNotice" tone="info" class="shell-maintenance">
          Modo mantenimiento activo: solo quienes administran el sistema pueden usar Trazo.
          <RouterLink :to="{ name: 'system-health' }">Ir a Sistema</RouterLink>
        </AppAlert>
        <!-- Otra ruta u otro registro crean la vista de nuevo; cambiar solo filtros de la URL no -->
        <RouterView v-if="!loading" v-slot="{ Component, route }">
          <component :is="Component" :key="`${String(route.name)}:${JSON.stringify(route.params)}`" />
        </RouterView>
      </main>
    </QPageContainer>
  </QLayout>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.shell-header {
  border-bottom: 1px solid $color-border;
  background: $color-surface;
  color: $color-text;
}

.shell-header__bar {
  display: flex;
  align-items: center;
  gap: $space-2;
  height: $header-height;
  padding: 0 $space-3;
}

.shell-header__menu {
  display: inline-flex;
  padding: $space-2;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text;
  cursor: pointer;
}

.shell-header__brand {
  font-size: $font-size-xl;
  text-decoration: none;
}

.shell-drawer {
  background: $color-surface;
}

.shell-content {
  padding: $space-4;
}

.shell-maintenance {
  margin-bottom: $space-4;
}

.shell-loading {
  display: grid;
  place-items: center;
  gap: $space-2;
  min-height: 50vh;
  font-size: 2.25rem;
}

.shell-loading__label {
  @include visually-hidden;
}

@include tablet {
  .shell-header__bar {
    padding: 0 $space-5;
  }

  .shell-content {
    padding: $space-5;
  }
}

@include laptop {
  .shell-content {
    padding: $space-6;
  }
}

// En monitor el contenido no se estira de más: se centra con un ancho máximo
@include desktop {
  .shell-content {
    max-width: $content-max-width;
    margin: 0 auto;
  }
}
</style>
