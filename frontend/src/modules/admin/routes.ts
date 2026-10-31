// Rutas de la administración, dentro del layout y con carga diferida. Documentado en docs/frontend/admin.md
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: 'administracion/usuarios',
    name: 'users',
    component: () => import('./presentation/UserListPage.vue'),
  },
  {
    path: 'administracion/usuarios/nuevo',
    name: 'user-create',
    component: () => import('./presentation/UserCreatePage.vue'),
  },
  {
    path: 'administracion/usuarios/:id',
    name: 'user-detail',
    component: () => import('./presentation/UserDetailPage.vue'),
  },
]

export default routes
