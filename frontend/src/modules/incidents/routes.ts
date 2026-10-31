// Rutas de incidentes, dentro del layout y con carga diferida.
// El listado se suma en la siguiente fase. Documentado en docs/frontend/incidents.md
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
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
