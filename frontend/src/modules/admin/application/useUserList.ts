// Estado del listado de usuarios ligado a la URL. Documentado en docs/frontend/admin.md
import { useRouteList } from '@/shared/listing/useRouteList'
import type { UserListCriteria } from '../domain/listing'
import type { UserSummary } from '../domain/user'
import { usersApi } from '../infrastructure/usersApi'
import { fromUserQuery, toUserQuery } from './listQuery'

export function useUserList() {
  return useRouteList<UserListCriteria, UserSummary>({
    fromQuery: fromUserQuery,
    toQuery: toUserQuery,
    fetch: (criteria, signal) => usersApi.list(criteria, signal),
  })
}
