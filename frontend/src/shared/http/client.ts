// Cliente HTTP único de la app. Ninguna vista lo usa directamente: pasan por los repositorios de cada módulo.
// Interceptores: token, identificador de petición, errores normalizados y reintentos seguros.
// Documentado en docs/frontend/http-y-errores.md
import axios, { type AxiosInstance, type InternalAxiosRequestConfig } from 'axios'
import { ApiError, toApiError } from './apiError'
import { httpEvents, type HttpEventBus } from './events'
import { MAINTENANCE_HEADER, maintenanceNotice } from './maintenance'
import { currentSocketId } from './socketId'
import { tokenStore, type TokenStore } from './tokenStore'

const RETRYABLE_STATUS = new Set([502, 504])
const MAX_RETRIES = 2
const SESSION_EXPIRED_CODE = 'UNAUTHENTICATED'

type RetryableConfig = InternalAxiosRequestConfig & { retryCount?: number }

export interface HttpClientOptions {
  baseURL?: string
  timeout?: number
  tokens?: TokenStore
  events?: HttpEventBus
  retryDelayMs?: number
}

// Solo se reintentan lecturas ante fallas de red o del gateway: nunca se repite una escritura
function shouldRetry(config: RetryableConfig, error: ApiError): boolean {
  const isRead = (config.method ?? 'get').toLowerCase() === 'get'
  const isTransient = error.kind === 'network' || RETRYABLE_STATUS.has(error.status ?? 0)

  return isRead && isTransient && (config.retryCount ?? 0) < MAX_RETRIES
}

const wait = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

// Una descarga (responseType blob) trae su error como archivo: se lee para conservar el código de la API
async function readBlobBody(raw: unknown): Promise<void> {
  const response = (raw as { response?: { data?: unknown } }).response
  if (!response || !(response.data instanceof Blob) || !response.data.type.includes('json')) return
  try {
    response.data = JSON.parse(await response.data.text()) as unknown
  } catch {
    // Sin un cuerpo legible, el error se arma solo con el estado HTTP
  }
}

export function createHttpClient(options: HttpClientOptions = {}): AxiosInstance {
  const tokens = options.tokens ?? tokenStore
  const events = options.events ?? httpEvents
  const retryDelayMs = options.retryDelayMs ?? 300

  const client = axios.create({
    baseURL: options.baseURL ?? import.meta.env.VITE_API_BASE_URL ?? '/api/v1',
    timeout: options.timeout ?? 15_000,
    headers: { Accept: 'application/json' },
  })

  client.interceptors.request.use((config) => {
    // Un pedido puede traer su propio token (el del segundo paso del ingreso); si no, va el de la sesión
    const token = tokens.get()
    if (token && !config.headers.Authorization) config.headers.Authorization = `Bearer ${token}`
    // Permite cruzar un error del navegador con los logs del servidor
    config.headers['X-Request-Id'] ??= crypto.randomUUID()
    // Así esta pestaña no recibe el aviso en tiempo real de su propio cambio
    const socket = currentSocketId()
    if (socket) config.headers['X-Socket-ID'] = socket
    return config
  })

  // Cada respuesta dice si sigue el mantenimiento: el aviso aparece y desaparece solo
  client.interceptors.response.use((response) => {
    maintenanceNotice.value = response.headers[MAINTENANCE_HEADER] === 'on'
    return response
  })

  client.interceptors.response.use(undefined, async (raw: unknown) => {
    await readBlobBody(raw)
    const error = toApiError(raw)
    const config = (raw as { config?: RetryableConfig }).config

    if (config && shouldRetry(config, error)) {
      config.retryCount = (config.retryCount ?? 0) + 1
      await wait(retryDelayMs * config.retryCount)
      return client.request(config)
    }

    // Solo una sesión vencida cierra la sesión; un 401 por credenciales incorrectas lo maneja el login
    if (error.code === SESSION_EXPIRED_CODE) {
      tokens.clear()
      events.emit('session-expired')
    }
    if (error.status === 503) events.emit('maintenance')

    throw error
  })

  return client
}

export const http = createHttpClient()
