// Rutas de la pantalla de sistema, dentro del layout y con carga diferida. Una ruta por pestaña; /sistema lleva a la
// primera que la persona puede ver. Documentado en docs/frontend/system.md
import type { RouteRecordRaw } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { SYSTEM_ROUTES } from './application/systemQuery'

const page = () => import('./presentation/SystemPage.vue')

const routes: RouteRecordRaw[] = [
  {
    path: 'sistema',
    name: 'system',
    // Como en la auditoría: en beforeEnter, cuando ya se conocen los permisos
    component: page,
    beforeEnter: () => ({
      name: useSessionStore().can('system.health.view') ? SYSTEM_ROUTES.health : SYSTEM_ROUTES.events,
    }),
  },
  { path: 'sistema/salud', name: SYSTEM_ROUTES.health, component: page, props: { tab: 'health' } },
  { path: 'sistema/eventos', name: SYSTEM_ROUTES.events, component: page, props: { tab: 'events' } },
]

export default routes
