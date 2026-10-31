<!-- Menú de la persona conectada, a la derecha del encabezado: quién es, su rol y cerrar sesión. -->
<!-- En celular y tablet solo se ve el ícono; desde portátil, también el nombre. Documentado en docs/frontend/sistema-de-diseno/layout.md -->
<script setup lang="ts">
import { QMenu } from 'quasar'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { LOGIN_ROUTE } from '@/modules/auth/application/authGuard'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import AppIcon from '@/shared/ui/AppIcon.vue'
import { useViewport } from '@/shared/ui/useViewport'

const session = useSessionStore()
const router = useRouter()
const { isLaptopUp } = useViewport()
const leaving = ref(false)

const roles = computed(() => session.user?.roles.map((role) => role.name).join(', ') ?? '')

async function logout(): Promise<void> {
  leaving.value = true
  await session.logout()
  await router.replace({ name: LOGIN_ROUTE })
}
</script>

<template>
  <div v-if="session.user" class="user-menu">
    <button class="user-menu__trigger" type="button" :aria-label="`Cuenta de ${session.user.name}`">
      <AppIcon name="user" />
      <span v-if="isLaptopUp" class="user-menu__name">{{ session.user.name }}</span>
      <AppIcon name="chevron-down" size="sm" />

      <QMenu anchor="bottom right" self="top right" :offset="[0, 6]">
        <div class="user-menu__identity">
          <p class="user-menu__identity-name">{{ session.user.name }}</p>
          <p class="user-menu__identity-detail">{{ session.user.email }}</p>
          <p class="user-menu__identity-detail">{{ roles }}</p>
        </div>
        <button class="user-menu__action" type="button" :disabled="leaving" @click="logout">
          <AppIcon name="logout" />
          Cerrar sesión
        </button>
      </QMenu>
    </button>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.user-menu {
  margin-left: auto;
}

.user-menu__trigger {
  display: inline-flex;
  align-items: center;
  gap: $space-2;
  min-height: $touch-target;
  padding: 0 $space-2;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text;
  font: inherit;
  font-size: $font-size-sm;
  cursor: pointer;

  &:hover {
    background: $color-page;
  }
}

.user-menu__name {
  font-weight: $font-weight-medium;
}

.user-menu__identity {
  padding: $space-3 $space-4;
  border-bottom: 1px solid $color-border;
}

.user-menu__identity-name {
  margin: 0;
  font-weight: $font-weight-semibold;
}

.user-menu__identity-detail {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.user-menu__action {
  display: flex;
  align-items: center;
  gap: $space-2;
  width: 100%;
  min-height: $touch-target;
  padding: 0 $space-4;
  border: 0;
  background: none;
  color: $color-text;
  font: inherit;
  font-size: $font-size-sm;
  cursor: pointer;

  &:hover:not(:disabled) {
    background: $color-page;
  }
}
</style>
