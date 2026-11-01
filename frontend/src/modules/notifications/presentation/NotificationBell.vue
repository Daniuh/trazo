<!-- Campana de notificaciones: en el dock (tablet en adelante) o en el encabezado (celular). Muestra cuántas hay sin
     leer y abre el centro de notificaciones. Se pone al día al entrar, con cada aviso de tiempo real y al reconectarse.
     Documentado en docs/frontend/notifications.md -->
<script setup lang="ts">
import { QTooltip } from 'quasar'
import { computed, onMounted, ref } from 'vue'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { useRealtime } from '@/modules/realtime/application/realtimeBus'
import AppIcon from '@/shared/ui/AppIcon.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { useNotificationsStore } from '../application/useNotificationsStore'
import { badgeText } from '../domain/notification'
import NotificationCenter from './NotificationCenter.vue'

const session = useSessionStore()
const store = useNotificationsStore()
const open = ref(false)
const { isMobile } = useViewport()

const label = computed(() => (store.total === 0 ? 'Notificaciones' : `Notificaciones, ${store.total} sin leer`))

onMounted(() => {
  if (session.user) void store.start(session.user.id)
})
// Mientras estuvo desconectado pudo llegar algo
useRealtime('reconnected', () => void store.changed())
</script>

<template>
  <!-- El nombre va como texto oculto y no en aria-label: así no choca con el número visible ("9+") -->
  <button class="bell" type="button" aria-haspopup="dialog" @click="open = true">
    <AppIcon name="bell" />
    <span class="bell__hidden">{{ label }}</span>
    <span v-if="store.total > 0" class="bell__count" aria-hidden="true">{{ badgeText(store.total) }}</span>
    <QTooltip v-if="!isMobile" anchor="top middle" self="bottom middle" :offset="[0, 10]">Notificaciones</QTooltip>
  </button>
  <NotificationCenter v-model:open="open" />
</template>

<style scoped lang="scss">
@use 'sass:list';
@use 'sass:map';
@use '@/design/tokens' as *;

.bell {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: $touch-target;
  height: $touch-target;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text;
  cursor: pointer;

  &:hover {
    background: $color-page;
  }
}

.bell__hidden {
  @include visually-hidden;
}

// Contador sobre la esquina de la campana: fondo rojo con texto blanco (tono "danger", contraste resuelto en tokens)
.bell__count {
  position: absolute;
  top: $space-1;
  right: 0;
  min-width: 1.125rem;
  padding: 0 0.3rem;
  border-radius: $radius-badge;
  background: list.nth(map.get($tones, 'danger'), 1);
  color: list.nth(map.get($tones, 'danger'), 2);
  font-size: $font-size-xs;
  font-weight: $font-weight-semibold;
  line-height: 1.125rem;
  text-align: center;
}
</style>
