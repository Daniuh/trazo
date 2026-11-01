// Pruebas de la capa de aplicación de la administración con la API simulada: catálogo de roles y permisos, ficha de
// una persona, crear una cuenta, roles (listado, formulario y eliminar) y el listado ligado a la URL.
// Documentado en docs/frontend/admin.md
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/shared/http'
import { flush, withSetup } from '@/shared/testing/withSetup'
import type { Role } from '../domain/role'
import type { UserDetail } from '../domain/user'
import { rolesApi } from '../infrastructure/rolesApi'
import { usersApi } from '../infrastructure/usersApi'
import { useAccessCatalogStore } from './useAccessCatalogStore'
import { useRoleForm, useRoleList } from './useRoles'
import { useUserCreate } from './useUserCreate'
import { useUserDetail } from './useUserDetail'
import { useUserList } from './useUserList'

vi.mock('../infrastructure/usersApi', () => ({
  usersApi: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    updateProfile: vi.fn(),
    changeRole: vi.fn(),
    changePermissions: vi.fn(),
    setActive: vi.fn(),
    resetMfa: vi.fn(),
    resetAccess: vi.fn(),
    roles: vi.fn(),
    permissions: vi.fn(),
  },
}))
vi.mock('../infrastructure/rolesApi', () => ({
  rolesApi: { list: vi.fn(), get: vi.fn(), create: vi.fn(), update: vi.fn(), remove: vi.fn() },
}))

const users = vi.mocked(usersApi)
const roles = vi.mocked(rolesApi)

const http = (code: string, details = {}) => new ApiError('http', code, { status: 422, ...details })
const user = { id: 'U1', name: 'Laura' } as unknown as UserDetail
const role: Role = {
  code: 'agent',
  name: 'Agente',
  level: 40,
  isSystem: true,
  permissions: ['incidents.view.own'],
  mfa: { required: true, graceLogins: 1 },
  members: 3,
  assignable: true,
  actions: { update: true, delete: false },
}

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
})

describe('catálogo de roles y permisos', () => {
  it('se pide una vez por persona y se vuelve a pedir si cambia o se invalida', async () => {
    users.roles.mockResolvedValue([{ code: 'agent', name: 'Agente', level: 40, assignable: true, permissions: [] }])
    users.permissions.mockResolvedValue([])
    const store = useAccessCatalogStore()

    await Promise.all([store.load('A'), store.load('A')])
    await store.load('A')
    expect(users.roles).toHaveBeenCalledTimes(1)
    expect(store.role('agent')?.name).toBe('Agente')
    expect(store.role('otro')).toBeNull()
    expect(store.assignableRoles).toHaveLength(1)

    store.invalidate()
    await store.load('A')
    await store.load('B')
    expect(users.roles).toHaveBeenCalledTimes(3)
  })

  it('guarda el error si la API falla', async () => {
    users.roles.mockRejectedValue(http('FORBIDDEN_ACTION', { status: 403 }))
    users.permissions.mockResolvedValue([])
    const store = useAccessCatalogStore()

    await store.load('A')

    expect(store.error?.code).toBe('FORBIDDEN_ACTION')
  })
})

describe('ficha de una persona', () => {
  it('carga la ficha y cada cambio la reemplaza por la que responde la API', async () => {
    users.get.mockResolvedValue(user)
    const changed = { ...user, name: 'Laura Gómez' }
    users.updateProfile.mockResolvedValue(changed)
    users.changeRole.mockResolvedValue(changed)
    users.changePermissions.mockResolvedValue(changed)
    users.setActive.mockResolvedValue(changed)
    users.resetMfa.mockResolvedValue(changed)
    users.resetAccess.mockResolvedValue(changed)
    const detail = useUserDetail('U1')

    await detail.load()
    expect(detail.user.value).toBe(user)

    await detail.updateProfile({ givenNames: 'Laura', familyNames: 'Gómez', email: 'laura@trazo.test' })
    await detail.changeRole('supervisor', '123456')
    await detail.changePermissions(['incidents.export'])
    await detail.setActive(false)
    await detail.resetMfa('123456')
    await detail.resetAccess('123456')

    expect(detail.user.value).toBe(changed)
    expect(users.changeRole).toHaveBeenCalledWith('U1', 'supervisor', '123456')
  })

  it('guarda el error de carga y deja seguir los errores de un cambio', async () => {
    users.get.mockRejectedValue(http('RESOURCE_NOT_FOUND', { status: 404 }))
    users.setActive.mockRejectedValue(http('USER_OUTRANKED', { status: 403 }))
    const detail = useUserDetail('U1')

    await detail.load()
    expect(detail.error.value?.code).toBe('RESOURCE_NOT_FOUND')
    expect(detail.loading.value).toBe(false)
    await expect(detail.setActive(false)).rejects.toMatchObject({ code: 'USER_OUTRANKED' })
  })
})

describe('crear una cuenta', () => {
  it('revisa los datos antes de enviar', () => {
    const form = useUserCreate()

    expect(form.validate()).toBe(false)
    expect(form.fieldErrors.value).toHaveProperty('role')
  })

  it('repite la clave de idempotencia con el mismo contenido y deja seguir el pedido del código', async () => {
    users.create.mockRejectedValueOnce(http('REAUTHENTICATION_REQUIRED')).mockResolvedValueOnce(user)
    const form = useUserCreate()
    Object.assign(form.draft, { name: 'Laura', email: 'laura@trazo.test', role: 'admin' })

    await expect(form.submit()).rejects.toMatchObject({ code: 'REAUTHENTICATION_REQUIRED' })
    expect(await form.submit('123456')).toBe(user)
    expect(users.create.mock.calls[0]?.[1]).toBe(users.create.mock.calls[1]?.[1])
  })

  it('pone los errores de la API en sus campos o en el formulario', async () => {
    users.create
      .mockRejectedValueOnce(http('VALIDATION_FAILED', { fieldErrors: { email: [{ code: 'ALREADY_TAKEN' }] } }))
      .mockRejectedValueOnce(http('ROLE_NOT_ASSIGNABLE'))
    const form = useUserCreate()

    expect(await form.submit()).toBeNull()
    expect(form.fieldErrors.value).toHaveProperty('email')
    expect(await form.submit()).toBeNull()
    expect(form.formError.value).toBeTruthy()
  })
})

describe('roles', () => {
  it('lista los roles y guarda el error', async () => {
    roles.list.mockResolvedValueOnce([role]).mockRejectedValueOnce(http('FORBIDDEN_ACTION', { status: 403 }))
    const list = useRoleList()

    await list.load()
    expect(list.roles.value).toEqual([role])
    await list.load()
    expect(list.error.value?.code).toBe('FORBIDDEN_ACTION')
  })

  it('carga un rol en el borrador, revisa los campos y al guardar invalida el catálogo', async () => {
    roles.get.mockResolvedValue(role)
    roles.update.mockResolvedValue({ ...role, name: 'Agentes' })
    const catalog = useAccessCatalogStore()
    const invalidate = vi.spyOn(catalog, 'invalidate')
    const form = useRoleForm('agent')

    await form.load()
    expect(form.draft.value.name).toBe('Agente')

    form.draft.value.level = 'cuarenta'
    expect(form.validate()).toBe(false)
    expect(form.fieldErrors.value).toHaveProperty('level')
    form.draft.value.level = '40'
    expect(form.validate()).toBe(true)

    expect((await form.save())?.name).toBe('Agentes')
    expect(invalidate).toHaveBeenCalled()
  })

  it('al crear exige el código, repite la clave con el mismo contenido y ubica el error del nivel', async () => {
    roles.create.mockRejectedValue(http('ROLE_LEVEL_NOT_ALLOWED'))
    const form = useRoleForm(null)

    expect(form.validate()).toBe(false)
    expect(form.fieldErrors.value).toHaveProperty('code')

    Object.assign(form.draft.value, { code: 'soporte', name: 'Soporte', level: '50' })
    expect(await form.save()).toBeNull()
    expect(await form.save()).toBeNull()
    expect(roles.create.mock.calls[0]?.[1]).toBe(roles.create.mock.calls[1]?.[1])
    expect(form.fieldErrors.value).toHaveProperty('level')

    roles.create.mockRejectedValue(http('ROLE_IN_USE', { status: 409 }))
    await form.save()
    expect(form.formError.value).toBeTruthy()
  })

  it('elimina, invalida el catálogo y devuelve el error si no se puede', async () => {
    roles.remove.mockResolvedValueOnce(undefined).mockRejectedValueOnce(http('ROLE_IN_USE', { status: 409 }))
    const form = useRoleForm('soporte')

    expect(await form.remove()).toBeNull()
    expect((await form.remove())?.code).toBe('ROLE_IN_USE')
    expect(await useRoleForm(null).remove()).toBeNull()
  })

  it('un error al cargar queda guardado', async () => {
    roles.get.mockRejectedValue(http('RESOURCE_NOT_FOUND', { status: 404 }))
    const form = useRoleForm('nada')

    await form.load()
    await useRoleForm(null).load()

    expect(form.loadError.value?.code).toBe('RESOURCE_NOT_FOUND')
  })
})

describe('listado de usuarios', () => {
  it('pide la página con los criterios de la URL', async () => {
    users.list.mockResolvedValue({ items: [], total: 0, lastPage: 1 })

    await withSetup(() => useUserList(), '/administracion/usuarios?search=laura')
    await flush()

    expect(users.list).toHaveBeenCalledWith(expect.objectContaining({ search: 'laura' }), expect.anything())
  })
})
