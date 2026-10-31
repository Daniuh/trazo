<!-- Estructura de la aplicación: sin encabezado, el contenido ocupa toda la pantalla y el menú va en un dock que flota
     abajo y centrado (AppDock), con solo lo permitido y, al final, la campana y la cuenta, en las cuatro vistas.
     Documentado en docs/frontend/sistema-de-diseno/layout.md -->
<script setup lang="ts">
import { QLayout, QPageContainer } from 'quasar'
import { computed, ref } from 'vue'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import NotificationBell from '@/modules/notifications/presentation/NotificationBell.vue'
import { useRealtimeConnection } from '@/modules/realtime/application/useRealtimeConnection'
import { useRealtimeNotices } from '@/modules/realtime/presentation/useRealtimeNotices'
import { maintenanceNotice } from '@/shared/http'
import AppLogo from '@/shared/ui/brand/AppLogo.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { visibleNavigation } from '../navigation'
import AppDock from './AppDock.vue'
import AppUserMenu from './AppUserMenu.vue'
import { useRouteLoading } from './useRouteLoading'

const { loading } = useRouteLoading()
const session = useSessionStore()
const navigation = computed(() => visibleNavigation(session.can))

// El shell solo existe con sesión: aquí vive la conexión de tiempo real y los avisos personales
useRealtimeConnection()
useRealtimeNotices()

// El foco va al contenido sin cambiar la URL (el router usa el historial, no el #)
const content = ref<HTMLElement>()
const focusContent = () => content.value?.focus()
</script>

<template>
  <QLayout view="lHh lpr fFf">
    <!-- Primer elemento al usar Tab: lleva directo al contenido sin recorrer el menú (WCAG 2.4.1) -->
    <a class="shell-skip" href="#contenido" @click.prevent="focusContent">Saltar al contenido</a>
    <QPageContainer>
      <main id="contenido" ref="content" class="shell-content" tabindex="-1">
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

    <AppDock :items="navigation">
      <NotificationBell />
      <AppUserMenu />
    </AppDock>
  </QLayout>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Solo para lectores y teclado hasta que recibe el foco; entonces aparece arriba a la izquierda
.shell-skip {
  @include visually-hidden;

  &:focus {
    position: fixed;
    z-index: $z-skip-link;
    top: $space-2;
    left: $space-2;
    width: auto;
    height: auto;
    padding: $space-2 $space-3;
    overflow: visible;
    clip-path: none;
    border-radius: $radius-control;
    background: $color-primary;
    color: $color-on-dark;
    font-weight: $font-weight-medium;
    white-space: normal;
  }
}

// El contenido recibe el foco solo por el enlace: sin borde de foco propio
.shell-content:focus {
  outline: none;
}

// Abajo, el espacio del dock: lo último de la página nunca queda tapado
.shell-content {
  padding: $space-4 $space-4 $dock-clearance-mobile;
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
  .shell-content {
    padding: $space-5 $space-5 $dock-clearance;
  }
}

@include laptop {
  .shell-content {
    padding: $space-6 $space-6 $dock-clearance;
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
