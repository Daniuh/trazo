// Modo mantenimiento visto desde una cuenta que puede seguir trabajando: la API lo indica con la cabecera
// X-Maintenance en cada respuesta, y el shell muestra el aviso mientras siga llegando. Documentado en docs/frontend/http-y-errores.md
import { ref } from 'vue'

export const MAINTENANCE_HEADER = 'x-maintenance'

export const maintenanceNotice = ref(false)
