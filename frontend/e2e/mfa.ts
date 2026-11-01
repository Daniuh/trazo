// El "teléfono" de las pruebas E2E: guarda la clave del MFA de cada cuenta de prueba y calcula sus códigos, igual que
// una aplicación de autenticación. Las claves quedan en e2e/.auth/ (fuera del repositorio) y solo sirven en local:
// si se borran, hay que recargar los datos de demostración (migrate:fresh --seed).
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { TOTP } from 'otpauth'

const STORE = join(dirname(fileURLToPath(import.meta.url)), '.auth', 'mfa.json')
const PERIOD = 30

interface Entry {
  secret: string
  // Último paso de 30 s usado: la API rechaza un código igual o anterior
  lastStep: number
}

function read(): Record<string, Entry> {
  return existsSync(STORE) ? (JSON.parse(readFileSync(STORE, 'utf8')) as Record<string, Entry>) : {}
}

function write(store: Record<string, Entry>): void {
  mkdirSync(dirname(STORE), { recursive: true })
  writeFileSync(STORE, JSON.stringify(store, null, 2))
}

export function saveSecret(email: string, secret: string): void {
  write({ ...read(), [email]: { secret, lastStep: 0 } })
}

export function secretOf(email: string): string {
  const entry = read()[email]
  if (!entry) {
    throw new Error(
      `${email} ya tiene MFA, pero su clave no está en e2e/.auth/mfa.json. ` +
        'Recarga los datos de demostración: docker compose -p trazo exec app php artisan migrate:fresh --seed',
    )
  }
  return entry.secret
}

// Un código nuevo: si el del paso actual ya se usó, espera al siguiente (como haría una persona)
export async function freshCode(email: string): Promise<string> {
  const secret = secretOf(email)
  const lastStep = read()[email]?.lastStep ?? 0
  let step = Math.floor(Date.now() / 1000 / PERIOD)
  if (step <= lastStep) {
    const nextWindow = (lastStep + 1) * PERIOD * 1000 - Date.now() + 500
    await new Promise((resolve) => setTimeout(resolve, nextWindow))
    step = lastStep + 1
  }

  write({ ...read(), [email]: { secret, lastStep: step } })
  return new TOTP({ secret, period: PERIOD }).generate({ timestamp: step * PERIOD * 1000 })
}
