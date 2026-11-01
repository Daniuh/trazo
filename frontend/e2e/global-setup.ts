// Antes de la suite: vacía los límites de frecuencia y los bloqueos del stack local (trazo:reset-limits), para poder
// repetirla sin esperar a que venzan (la recuperación de contraseña permite 3 cada 15 minutos). Contra otro servidor
// (E2E_BASE_URL) no hace nada. Documentado en docs/calidad/estrategia-de-pruebas.md
import { execSync } from 'node:child_process'

export default function globalSetup(): void {
  if (process.env.E2E_BASE_URL) return

  try {
    execSync('docker compose -p trazo exec -T app php artisan trazo:reset-limits', { stdio: 'pipe' })
  } catch {
    console.warn(
      'No se pudieron vaciar los límites de frecuencia; si la suite se repite seguido, alguna prueba puede chocar con ellos.',
    )
  }
}
