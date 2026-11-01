<!-- Pantalla de sistema: dos pestañas, cada una con su permiso. Salud (estado de cada pieza y sus acciones) y Eventos
     (fallas y alertas agrupadas). Documentado en docs/frontend/system.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'
import { SYSTEM_ROUTES } from '../application/systemQuery'
import EventsView from './events/EventsView.vue'
import HealthView from './health/HealthView.vue'

type SystemTab = keyof typeof SYSTEM_ROUTES

const props = defineProps<{ tab: SystemTab }>()

const router = useRouter()
const session = useSessionStore()

const tabs = computed(() =>
  [
    { label: 'Salud', value: 'health' as const, permission: 'system.health.view' },
    { label: 'Eventos', value: 'events' as const, permission: 'system.logs.view' },
  ].filter((option) => session.can(option.permission)),
)
const tabModel = computed({
  get: () => props.tab,
  set: (value: SystemTab) => void router.push({ name: SYSTEM_ROUTES[value] }),
})
</script>

<template>
  <div class="system-page">
    <header class="system-page__header">
      <h1 class="system-page__title">Sistema</h1>
      <AppSegmented v-if="tabs.length > 1" v-model="tabModel" label="Sección" :options="tabs" />
    </header>

    <HealthView v-if="tab === 'health'" />
    <EventsView v-else />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.system-page {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.system-page__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
}

.system-page__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

@include laptop {
  .system-page__title {
    font-size: $font-size-2xl;
  }
}
</style>
