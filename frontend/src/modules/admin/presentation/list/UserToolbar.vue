<!-- Barra del listado de usuarios. Portátil y monitor: búsqueda y los tres filtros en una fila. Celular y tablet:
     búsqueda, "Filtros (n)" (abre el panel) y "Ordenar por". Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppFilterSheet from '@/shared/ui/AppFilterSheet.vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import { useViewport } from '@/shared/ui/useViewport'
import {
  activeUserFilterCount,
  DEFAULT_USER_CRITERIA,
  type UserListCriteria,
  type UserSortField,
} from '../../domain/listing'
import type { RoleOption } from '../../domain/user'
import { USER_SORT_CHOICES } from '../userTexts'
import UserFilterFields from './UserFilterFields.vue'

const SEARCH_DELAY_MS = 400

const props = defineProps<{ criteria: UserListCriteria; roles: RoleOption[] }>()
const emit = defineEmits<{ change: [changes: Partial<UserListCriteria>] }>()

const { isLaptopUp } = useViewport()
const search = ref(props.criteria.search)
let timer: ReturnType<typeof setTimeout> | undefined

// Se busca al dejar de escribir
watch(search, (value) => {
  clearTimeout(timer)
  timer = setTimeout(() => emit('change', { search: value.trim() }), SEARCH_DELAY_MS)
})
watch(
  () => props.criteria.search,
  (value) => {
    if (value !== search.value.trim()) search.value = value
  },
)

// Panel de celular y tablet: se edita en borrador y se aplica de una vez
const sheetOpen = ref(false)
const draft = ref<UserListCriteria>({ ...props.criteria })
watch(sheetOpen, (open) => {
  if (open) draft.value = { ...props.criteria }
})

const filterCount = computed(() => activeUserFilterCount(props.criteria))

const sortModel = computed({
  get: () => `${props.criteria.descending ? '-' : ''}${props.criteria.sort}`,
  set: (value: string | string[] | null) => {
    const raw = String(value ?? DEFAULT_USER_CRITERIA.sort)
    emit('change', { sort: raw.replace(/^-/, '') as UserSortField, descending: raw.startsWith('-') })
  },
})

function applySheet(): void {
  emit('change', { roles: draft.value.roles, status: draft.value.status, mfa: draft.value.mfa })
  sheetOpen.value = false
}

function clearSheet(): void {
  draft.value = { ...draft.value, roles: [], status: null, mfa: null }
}
</script>

<template>
  <div class="user-toolbar">
    <div class="user-toolbar__search">
      <AppTextField v-model="search" label="Buscar" type="search" placeholder="Nombre o correo" :maxlength="100" />
    </div>

    <UserFilterFields v-if="isLaptopUp" :criteria="criteria" :roles="roles" @change="emit('change', $event)" />

    <div v-else class="user-toolbar__compact">
      <AppButton variant="secondary" icon="filter" @click="sheetOpen = true">
        Filtros{{ filterCount > 0 ? ` (${filterCount})` : '' }}
      </AppButton>
      <AppSelect v-model="sortModel" class="user-toolbar__sort" label="Ordenar por" :options="USER_SORT_CHOICES" />
      <AppFilterSheet v-model:open="sheetOpen" @apply="applySheet" @clear="clearSheet">
        <UserFilterFields :criteria="draft" :roles="roles" @change="draft = { ...draft, ...$event }" />
      </AppFilterSheet>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.user-toolbar {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

.user-toolbar__compact {
  display: grid;
  grid-template-columns: auto 1fr;
  align-items: end;
  gap: $space-2;
}

// La etiqueta "Ordenar por" queda para lectores de pantalla; el texto del selector ya lo explica
.user-toolbar__sort :deep(.app-field__label) {
  @include visually-hidden;
}

// En portátil todo va en una fila: la búsqueda toma el espacio libre
@include laptop {
  .user-toolbar {
    flex-flow: row wrap;
    align-items: flex-start;
  }

  .user-toolbar__search {
    flex: 1;
    min-width: $filter-field-width;
  }
}
</style>
