// Error normalizado: cualquier falla (HTTP, red, tiempo de espera) llega a la app con la misma forma.
// Documentado en docs/frontend/http-y-errores.md
import { AxiosError, isAxiosError } from 'axios'

export type ErrorParams = Record<string, string | number | boolean>

export interface FieldError {
  code: string
  params?: ErrorParams
}

export type ApiErrorKind = 'http' | 'network' | 'timeout' | 'canceled'

// Códigos propios del cliente, para fallas que nunca llegaron al servidor
export const ClientErrorCode = {
  Network: 'NETWORK_ERROR',
  Timeout: 'TIMEOUT',
  Canceled: 'REQUEST_CANCELED',
  Unknown: 'UNKNOWN_ERROR',
} as const

interface ProblemBody {
  code?: string
  params?: ErrorParams
  errors?: Record<string, FieldError[]>
  trace_id?: string
}

export interface ApiErrorDetails {
  status?: number | null
  params?: ErrorParams
  fieldErrors?: Record<string, FieldError[]>
  traceId?: string | null
  retryAfter?: number | null
}

export class ApiError extends Error {
  readonly kind: ApiErrorKind
  readonly code: string
  readonly status: number | null
  readonly params: ErrorParams
  readonly fieldErrors: Record<string, FieldError[]>
  readonly traceId: string | null
  readonly retryAfter: number | null

  constructor(kind: ApiErrorKind, code: string, details: ApiErrorDetails = {}) {
    super(code)
    this.name = 'ApiError'
    this.kind = kind
    this.code = code
    this.status = details.status ?? null
    this.params = details.params ?? {}
    this.fieldErrors = details.fieldErrors ?? {}
    this.traceId = details.traceId ?? null
    this.retryAfter = details.retryAfter ?? null
  }

  get isValidation(): boolean {
    return Object.keys(this.fieldErrors).length > 0
  }
}

export function toApiError(error: unknown): ApiError {
  if (error instanceof ApiError) return error
  if (!isAxiosError(error)) return new ApiError('network', ClientErrorCode.Unknown)

  if (error.code === AxiosError.ERR_CANCELED) return new ApiError('canceled', ClientErrorCode.Canceled)
  if (error.code === AxiosError.ECONNABORTED || error.code === AxiosError.ETIMEDOUT) {
    return new ApiError('timeout', ClientErrorCode.Timeout)
  }

  const response = error.response
  if (!response) return new ApiError('network', ClientErrorCode.Network)

  const body = (response.data ?? {}) as ProblemBody
  const retryAfter = Number(response.headers['retry-after'])

  return new ApiError('http', body.code ?? ClientErrorCode.Unknown, {
    status: response.status,
    params: body.params,
    fieldErrors: body.errors,
    traceId: body.trace_id ?? (response.headers['x-request-id'] as string | undefined),
    retryAfter: Number.isFinite(retryAfter) ? retryAfter : null,
  })
}
