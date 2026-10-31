// La URL del listado de usuarios y los parámetros de la API: ida y vuelta sin perder nada y valores inválidos
// ignorados. También la revisión visual de nombre y correo.
import { describe, expect, it } from 'vitest'
import { DEFAULT_USER_CRITERIA, type UserListCriteria } from '../domain/listing'
import { toListParams } from '../infrastructure/usersApi'
import { fromUserQuery, toUserQuery } from './listQuery'
import { profileErrors } from './profileRules'

const full: UserListCriteria = {
  page: 2,
  perPage: 50,
  sort: 'last_login_at',
  descending: true,
  search: 'gómez',
  roles: ['agent', 'supervisor'],
  status: 'inactive',
  mfa: 'disabled',
}

describe('listQuery de usuarios', () => {
  it('ida y vuelta por la URL conserva todos los criterios', () => {
    const query = Object.fromEntries(Object.entries(toUserQuery(full)).map(([key, value]) => [key, String(value)]))

    expect(fromUserQuery(query)).toEqual(full)
  })

  it('con los valores por defecto la URL queda vacía', () => {
    expect(toUserQuery(DEFAULT_USER_CRITERIA)).toEqual({})
  })

  it('ignora valores que no reconoce', () => {
    expect(fromUserQuery({ sort: '-password', status: 'deleted', mfa: 'x', per_page: '7', page: '-1' })).toEqual({
      ...DEFAULT_USER_CRITERIA,
      status: 'active',
      mfa: 'enabled',
    })
  })

  it('arma los parámetros de la API', () => {
    expect(toListParams(full)).toEqual({
      page: 2,
      per_page: 50,
      sort: '-last_login_at',
      search: 'gómez',
      'filter[role]': 'agent,supervisor',
      'filter[status]': 'inactive',
      'filter[mfa]': 'disabled',
    })
  })
})

describe('revisión visual de los datos', () => {
  it('pide nombre y un correo con forma de correo', () => {
    expect(profileErrors({ name: ' ', email: 'ana' })).toEqual({
      name: 'Este campo es obligatorio.',
      email: 'Escribe un correo válido, por ejemplo nombre@empresa.com.',
    })
    expect(profileErrors({ name: 'Ana Torres', email: 'ana@empresa.com' })).toEqual({})
  })
})
