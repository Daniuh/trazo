// Rutas de la auditoría, dentro del layout y con carga diferida. Una ruta por pestaña; /auditoria lleva a la primera
// que la persona puede ver. Documentado en docs/frontend/audit.md
import type { RouteRecordRaw } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { AUDIT_ROUTES } from './application/auditLink'

const page = () => import('./presentation/AuditPage.vue')

const routes: RouteRecordRaw[] = [
  {
    path: 'auditoria',
    name: 'audit',
    // Nunca se dibuja: lleva a una pestaña. Va en beforeEnter (no en redirect) porque corre después de la guarda que
    // recupera la sesión, y así ya se conocen los permisos
    component: page,
    beforeEnter: () => ({
      name: useSessionStore().can('audit.incidents.view') ? AUDIT_ROUTES.incidents : AUDIT_ROUTES.admin,
    }),
  },
  { path: 'auditoria/incidentes', name: AUDIT_ROUTES.incidents, component: page, props: { tab: 'incidents' } },
  { path: 'auditoria/administracion', name: AUDIT_ROUTES.admin, component: page, props: { tab: 'admin' } },
]

export default routes
