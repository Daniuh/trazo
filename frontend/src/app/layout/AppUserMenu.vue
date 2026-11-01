<!-- La cuenta de la persona conectada: un círculo con sus iniciales que abre un menú con su nombre y rol, "Mi cuenta" y
     "Cerrar sesión". Va al final del dock (tablet en adelante) o en el encabezado (celular); el menú se abre donde cabe.
     Cerrar sesión pide confirmación, por si se tocó sin querer. Documentado en docs/frontend/sistema-de-diseno/layout.md -->
<script setup lang="ts">
import { ClosePopup, QTooltip } from 'quasar'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { LOGIN_ROUTE } from '@/modules/auth/application/authGuard'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { initials } from '@/shared/format/initials'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppMenu from '@/shared/ui/AppMenu.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import { useViewport } from '@/shared/ui/useViewport'

const session = useSessionStore()
const router = useRouter()
const { confirm } = useFeedback()
const { isMobile } = useViewport()
const leaving = ref(false)
const vClosePopup = ClosePopup

const roles = computed(() => session.user?.roles.map((role) => role.name).join(', ') ?? '')
const letters = computed(() => initials(session.user?.name ?? ''))
const label = computed(() => `Tu cuenta: ${session.user?.name ?? ''}`)

async function logout(): Promise<void> {
  const ok = await confirm({
    title: '¿Cerrar sesión?',
    message: 'Tendrás que volver a ingresar con tu correo, tu contraseña y el código de verificación.',
    confirmLabel: 'Cerrar sesión',
  })
  if (!ok) return
  leaving.value = true
  await session.logout()
  await router.replace({ name: LOGIN_ROUTE })
}
</script>

<template>
  <button v-if="session.user" class="account" type="button" aria-haspopup="menu">
    <!-- El nombre va como texto oculto y no en aria-label: así el nombre accesible no choca con las iniciales visibles -->
    <span class="account__avatar" aria-hidden="true">{{ letters }}</span>
    <span class="account__hidden">{{ label }}</span>
    <QTooltip v-if="!isMobile" anchor="top middle" self="bottom middle" :offset="[0, 10]">Tu cuenta</QTooltip>
    <AppMenu align="right">
      <div class="account__menu" role="menu" :aria-label="label">
        <div class="account__who">
          <span class="account__name">{{ session.user.name }}</span>
          <span class="account__role">{{ roles }}</span>
        </div>
        <RouterLink v-close-popup class="account__option" :to="{ name: 'account' }" role="menuitem">
          <AppIcon name="user" />
          Mi cuenta
        </RouterLink>
        <button v-close-popup class="account__option" type="button" role="menuitem" :disabled="leaving" @click="logout">
          <AppIcon name="logout" />
          Cerrar sesión
        </button>
      </div>
    </AppMenu>
  </button>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Relativo para que el texto oculto quede dentro del botón
.account {
  position: relative;
  display: inline-flex;
  flex: none;
  align-items: center;
  justify-content: center;
  width: $touch-target;
  height: $touch-target;
  padding: 0;
  border: 0;
  border-radius: $radius-control;
  background: none;
  cursor: pointer;

  &:hover {
    background: $color-page;
  }
}

// Círculo azul oscuro de marca con letras blancas (5:1), como la sección activa del menú
.account__avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: $avatar-size;
  height: $avatar-size;
  border-radius: 50%;
  background: $color-primary;
  color: $color-on-dark;
  font-size: $font-size-xs;
  font-weight: $font-weight-semibold;
}

.account__hidden {
  @include visually-hidden;
}

.account__menu {
  display: flex;
  flex-direction: column;
  min-width: $menu-min-width;
  padding: $space-1 0;
}

.account__who {
  display: flex;
  flex-direction: column;
  padding: $space-2 $space-3 $space-3;
  border-bottom: 1px solid $color-border;
  margin-bottom: $space-1;
}

.account__name {
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.account__role {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.account__option {
  @include menu-item;

  text-decoration: none;
}
</style>
