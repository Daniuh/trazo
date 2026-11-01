// Convierte cada registro de la auditoría en textos claros: sobre qué fue y qué cambió, campo por campo
// ("Estado: Abierto → En progreso", "Rol: Agente → Supervisor"). Los códigos de roles y permisos se muestran con su
// nombre; si ya no existen, con su código. Documentado en docs/frontend/audit.md
import { mfaPolicyText } from '@/modules/admin/presentation/userTexts'
import { formatDateTime } from '@/shared/format/dates'
import { formatNumber } from '@/shared/format/numbers'
import type { AdminAuditEntry, AuditCatalog, AuditEntry, CodeName, IncidentAuditEntry } from '../domain/audit'

export interface ChangeRow {
  label: string
  value: string
}

const INCIDENT_FIELDS: Record<string, string> = {
  title: 'Título',
  description: 'Descripción',
  priority: 'Prioridad',
  due_date: 'Vencimiento',
  status: 'Estado',
  assignee: 'Asignado a',
}

const isRecord = (value: unknown): value is Record<string, unknown> => typeof value === 'object' && value !== null
const isChange = (value: unknown): value is { from: unknown; to: unknown } =>
  isRecord(value) && 'from' in value && 'to' in value
const arrow = (from: string, to: string) => `${from} → ${to}`
const nameIn = (list: CodeName[], code: unknown) => list.find((item) => item.code === code)?.name ?? String(code)

function incidentValue(field: string, value: unknown): string {
  if (value === null || value === undefined) return field === 'assignee' ? 'Sin asignar' : '—'
  if (field === 'due_date') return formatDateTime(String(value))
  if (isRecord(value)) return String(value.name ?? '')
  return String(value)
}

function incidentRows(entry: IncidentAuditEntry): ChangeRow[] {
  const rows = Object.entries(entry.changes ?? {}).map(([field, change]) => ({
    label: INCIDENT_FIELDS[field] ?? field,
    value:
      'from' in change
        ? arrow(incidentValue(field, change.from), incidentValue(field, change.to))
        : incidentValue(field, change.to),
  }))
  return entry.note ? [...rows, { label: 'Nota', value: entry.note }] : rows
}

function mfaText(value: unknown): string {
  if (!isRecord(value)) return '—'
  return mfaPolicyText({ required: Boolean(value.required), graceLogins: Number(value.grace_logins ?? 0) })
}

function permissionList(catalog: AuditCatalog, codes: unknown): string {
  return Array.isArray(codes) && codes.length > 0
    ? codes.map((code) => nameIn(catalog.permissions, code)).join(', ')
    : ''
}

function range(from: unknown, to: unknown): string {
  const date = (value: unknown) => formatDateTime(String(value))
  if (from && to) return `Del ${date(from)} al ${date(to)}`
  if (from) return `Desde ${date(from)}`
  return to ? `Hasta ${date(to)}` : ''
}

const names = (value: unknown) =>
  Array.isArray(value) ? value.map((item) => (isRecord(item) ? String(item.name) : String(item))).join(', ') : ''

// Filtros con los que se exportaron incidentes: estados y prioridades llegan con su nombre, personas por su id
function exportCriteriaRows(criteria: Record<string, unknown>, catalog: AuditCatalog): ChangeRow[] {
  const person = (id: unknown) => catalog.people.find((item) => item.id === id)?.name ?? String(id)
  const assignees = [
    ...(Array.isArray(criteria.assignee) ? criteria.assignee.map(person) : []),
    ...(criteria.unassigned ? ['Sin asignar'] : []),
  ]
  const rows: ChangeRow[] = [
    { label: 'Búsqueda', value: criteria.search ? `«${String(criteria.search)}»` : '' },
    { label: 'Estado', value: names(criteria.status) },
    { label: 'Prioridad', value: names(criteria.priority) },
    { label: 'Asignado a', value: assignees.join(', ') },
    { label: 'Creado por', value: criteria.creator ? person(criteria.creator) : '' },
    { label: 'Creación', value: range(criteria.created_from, criteria.created_to) },
    { label: 'Vencimiento', value: range(criteria.due_from, criteria.due_to) },
    { label: 'Solo vencidos', value: criteria.overdue ? 'Sí' : '' },
    { label: 'Eliminados', value: criteria.deleted ? 'Sí' : '' },
  ].filter((row) => row.value !== '')
  return rows.length > 0 ? rows : [{ label: 'Filtros', value: 'Ninguno: todo lo que la persona podía ver' }]
}

// Una línea por dato del detalle; lo que no cambió (null) no se muestra
function adminRows(entry: AdminAuditEntry, catalog: AuditCatalog): ChangeRow[] {
  const rows: ChangeRow[] = []
  const push = (label: string, value: string) => {
    if (value) rows.push({ label, value })
  }
  const plain = (value: unknown, format: (raw: unknown) => string = String) =>
    isChange(value) ? arrow(format(value.from), format(value.to)) : value === null ? '' : format(value)
  const showsCode = entry.type.code === 'role_created' || entry.type.code === 'role_deleted'

  for (const [key, value] of Object.entries(entry.details ?? {})) {
    if (key === 'name') push('Nombre', plain(value))
    else if (key === 'email') push('Correo', plain(value))
    else if (key === 'role')
      push(
        'Rol',
        plain(value, (code) => nameIn(catalog.roles, code)),
      )
    else if (key === 'code' && showsCode) push('Código', String(value))
    else if (key === 'level') push('Nivel', plain(value))
    else if (key === 'rows') push('Filas exportadas', formatNumber(Number(value)))
    else if (key === 'count') push('Jobs reintentados', formatNumber(Number(value)))
    else if (key === 'job') push('Job', String(value))
    else if (key === 'queue') push('Cola', String(value))
    else if (key === 'criteria' && isRecord(value)) rows.push(...exportCriteriaRows(value, catalog))
    else if (key === 'mfa') push('Verificación en dos pasos', plain(value, mfaText))
    else if (key === 'added') push('Permisos concedidos', permissionList(catalog, value))
    else if (key === 'removed') push('Permisos quitados', permissionList(catalog, value))
    else if (key === 'permissions' && Array.isArray(value)) push('Permisos', permissionList(catalog, value))
    // Al cambiar de rol se borran las excepciones de la persona: se muestran las que tenía
    else if (key === 'cleared' && isRecord(value)) {
      push('Permisos adicionales que se quitaron', permissionList(catalog, value.granted))
      push('Permisos del rol que se devolvieron', permissionList(catalog, value.denied))
    } else if (key === 'permissions' && isRecord(value)) {
      push('Permisos agregados', permissionList(catalog, value.added))
      push('Permisos quitados', permissionList(catalog, value.removed))
    }
  }
  return rows
}

export function changeRows(entry: AuditEntry, catalog: AuditCatalog | null): ChangeRow[] {
  if (entry.tab === 'incidents') return incidentRows(entry)
  return catalog ? adminRows(entry, catalog) : []
}

// Resumen de una línea para la tabla y las tarjetas: el primer cambio y cuántos más hay
export function changeSummary(entry: AuditEntry, catalog: AuditCatalog | null): string {
  const rows = changeRows(entry, catalog)
  const first = rows[0]
  if (!first) return ''
  const more = rows.length > 1 ? ` (+${rows.length - 1})` : ''
  return `${first.label}: ${first.value}${more}`
}

export function targetText(entry: AuditEntry): string {
  if (entry.tab === 'incidents') return `${entry.incident.code} · ${entry.incident.title}`
  if (entry.targetUser) return entry.targetUser.name
  if (entry.targetRole) return `Rol ${entry.targetRole.name}${entry.targetRole.deleted ? ' (eliminado)' : ''}`
  if (entry.type.code === 'incidents_exported') return 'Incidentes'
  if (entry.type.code.startsWith('system_')) return 'Sistema'
  return '—'
}
