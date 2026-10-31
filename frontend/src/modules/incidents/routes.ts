// Rutas de incidentes, dentro del layout y con carga diferida.
// Documentado en docs/frontend/incidents.md
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  { path: 'incidentes', name: 'incidents', component: () => import('./presentation/IncidentListPage.vue') },
  { path: 'incidentes/nuevo', name: 'incident-create', component: () => import('./presentation/IncidentFormPage.vue') },
  {
    path: 'incidentes/:id',
    name: 'incident-detail',
    component: () => import('./presentation/IncidentDetailPage.vue'),
  },
  {
    path: 'incidentes/:id/editar',
    name: 'incident-edit',
    component: () => import('./presentation/IncidentFormPage.vue'),
  },
]

export default routes
