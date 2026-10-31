// Pruebas del cliente HTTP: token, identificador de petición, errores normalizados y reintentos.
import type { AxiosAdapter, AxiosResponse, InternalAxiosRequestConfig } from 'axios'
import { AxiosError } from 'axios'
import { describe, expect, it, vi } from 'vitest'
import { ApiError } from './apiError'
import { createHttpClient } from './client'
import { createHttpEvents } from './events'
import { createTokenStore } from './tokenStore'

type Reply = { status: number; data?: unknown; headers?: Record<string, string> } | 'network'

// Adaptador falso: responde en orden lo que cada prueba prepara y guarda las peticiones recibidas
function fakeServer(...replies: Reply[]) {
  const requests: InternalAxiosRequestConfig[] = []
  const adapter: AxiosAdapter = async (config) => {
    requests.push(config)
    const reply = replies.shift() ?? { status: 200 }
    if (reply === 'network') throw new AxiosError('Network Error', AxiosError.ERR_NETWORK, config)

    const response = {
      status: reply.status,
      data: reply.data,
      headers: reply.headers ?? {},
      config,
      statusText: '',
    } as AxiosResponse
    if (reply.status >= 400) throw new AxiosError('fail', AxiosError.ERR_BAD_RESPONSE, config, null, response)
    return response
  }
  return { adapter, requests }
}

function setup(...replies: Reply[]) {
  const server = fakeServer(...replies)
  const tokens = createTokenStore(null)
  const events = createHttpEvents()
  const client = createHttpClient({ tokens, events, retryDelayMs: 0 })
  client.defaults.adapter = server.adapter
  return { client, tokens, events, requests: server.requests }
}

describe('cliente HTTP', () => {
  it('envía el token y un identificador de petición', async () => {
    const { client, tokens, requests } = setup({ status: 200 })
    tokens.set('abc')

    await client.get('/incidents')

    expect(requests[0]?.headers.Authorization).toBe('Bearer abc')
    expect(requests[0]?.headers['X-Request-Id']).toMatch(/^[0-9a-f-]{36}$/)
  })

  it('no envía Authorization si no hay sesión', async () => {
    const { client, requests } = setup({ status: 200 })

    await client.get('/catalogs')

    expect(requests[0]?.headers.Authorization).toBeUndefined()
  })

  it('convierte un error de la API en un ApiError con su código, campos y trace_id', async () => {
    const { client } = setup({
      status: 422,
      data: {
        code: 'VALIDATION_FAILED',
        errors: { title: [{ code: 'TOO_LONG', params: { max: 150 } }] },
        trace_id: 'T1',
      },
    })

    const error = await client.post('/incidents', {}).catch((e: unknown) => e)

    expect(error).toBeInstanceOf(ApiError)
    expect(error).toMatchObject({ kind: 'http', status: 422, code: 'VALIDATION_FAILED', traceId: 'T1' })
    expect((error as ApiError).fieldErrors.title?.[0]).toEqual({ code: 'TOO_LONG', params: { max: 150 } })
  })

  it('cierra la sesión y avisa cuando la API responde 401', async () => {
    const { client, tokens, events } = setup({ status: 401, data: { code: 'UNAUTHENTICATED' } })
    const onExpired = vi.fn()
    events.on('session-expired', onExpired)
    tokens.set('vencido')

    await client.get('/auth/me').catch(() => undefined)

    expect(tokens.get()).toBeNull()
    expect(onExpired).toHaveBeenCalledOnce()
  })

  it('no toca la sesión cuando el 401 es por credenciales incorrectas', async () => {
    const { client, tokens, events } = setup({ status: 401, data: { code: 'INVALID_CREDENTIALS' } })
    const onExpired = vi.fn()
    events.on('session-expired', onExpired)
    tokens.set('vigente')

    await client.post('/auth/login', {}).catch(() => undefined)

    expect(tokens.get()).toBe('vigente')
    expect(onExpired).not.toHaveBeenCalled()
  })

  it('avisa el modo mantenimiento ante un 503', async () => {
    const { client, events } = setup({ status: 503, data: { code: 'SERVICE_UNAVAILABLE' } })
    const onMaintenance = vi.fn()
    events.on('maintenance', onMaintenance)

    await client.get('/incidents').catch(() => undefined)

    expect(onMaintenance).toHaveBeenCalledOnce()
  })

  it('lee Retry-After en un 429', async () => {
    const { client } = setup({ status: 429, data: { code: 'TOO_MANY_REQUESTS' }, headers: { 'retry-after': '30' } })

    const error = (await client.post('/auth/login', {}).catch((e: unknown) => e)) as ApiError

    expect(error.retryAfter).toBe(30)
  })

  it('reintenta una lectura ante una falla de red y termina bien', async () => {
    const { client, requests } = setup('network', { status: 200, data: { ok: true } })

    const response = await client.get('/incidents')

    expect(response.data).toEqual({ ok: true })
    expect(requests).toHaveLength(2)
  })

  it('nunca reintenta una escritura', async () => {
    const { client, requests } = setup('network', { status: 201 })

    const error = (await client.post('/incidents', {}).catch((e: unknown) => e)) as ApiError

    expect(error.code).toBe('NETWORK_ERROR')
    expect(requests).toHaveLength(1)
  })

  it('deja de reintentar después de dos intentos', async () => {
    const { client, requests } = setup('network', 'network', 'network')

    const error = (await client.get('/incidents').catch((e: unknown) => e)) as ApiError

    expect(error.kind).toBe('network')
    expect(requests).toHaveLength(3)
  })
})
