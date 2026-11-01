<!-- Filtros de la auditoría: fecha, persona que hizo el cambio y tipo; en incidentes, el código del incidente; en
     administración, la persona y el rol afectados. En portátil y monitor van en la barra; en celular y tablet, dentro
     del panel de filtros. Documentado en docs/frontend/audit.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import AppDateRangeField from '@/shared/ui/form/AppDateRangeField.vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import type { AuditCatalog, AuditCriteria, AuditTab } from '../../domain/audit'

const CODE_DELAY_MS = 500
const ALL = 'all'

const props = defineProps<{
  tab: AuditTab
  criteria: AuditCriteria
  catalog: AuditCatalog | null
  incidentError?: string
  // En el panel de filtros se edita un borrador: el código pasa de inmediato, sin esperar
  draft?: boolean
}>()
const emit = defineEmits<{ change: [changes: Partial<AuditCriteria>] }>()

const options = (list: { id?: string; code?: string; name: string }[]) =>
  list.map((item) => ({ label: item.name, value: item.id ?? item.code ?? '' }))
// Los selectores de una sola opción empiezan con "Cualquiera", que quita el filtro
const people = computed(() => [{ label: 'Cualquiera', value: ALL }, ...options(props.catalog?.people ?? [])])
const types = computed(() =>
  options((props.tab === 'incidents' ? props.catalog?.incidentTypes : props.catalog?.adminTypes) ?? []),
)
const roles = computed(() => [{ label: 'Todos', value: ALL }, ...options(props.catalog?.roles ?? [])])

const single = (key: 'actor' | 'target' | 'role') =>
  computed({
    get: () => props.criteria[key] ?? ALL,
    set: (value: string | string[] | null) =>
      emit('change', { [key]: typeof value === 'string' && value !== ALL ? value : null }),
  })
const actor = single('actor')
const target = single('target')
const role = single('role')
const typeModel = computed({
  get: () => props.criteria.types,
  set: (value: string | string[] | null) => emit('change', { types: Array.isArray(value) ? value : [] }),
})
const from = computed({ get: () => props.criteria.from, set: (value) => emit('change', { from: value }) })
const to = computed({ get: () => props.criteria.to, set: (value) => emit('change', { to: value }) })

// En la barra, el código del incidente se aplica al dejar de escribir
const code = ref(props.tab === 'incidents' ? (props.criteria.target ?? '') : '')
let timer: ReturnType<typeof setTimeout> | undefined
watch(code, (value) => {
  clearTimeout(timer)
  const apply = () => emit('change', { target: value.trim() || null })
  if (props.draft) apply()
  else timer = setTimeout(apply, CODE_DELAY_MS)
})
watch(
  () => props.criteria.target,
  (value) => {
    if (props.tab === 'incidents' && (value ?? '') !== code.value.trim()) code.value = value ?? ''
  },
)
</script>

<template>
  <div class="audit-filter-fields">
    <AppDateRangeField v-model:from="from" v-model:to="to" label="Fecha" placeholder="Últimos 30 días" />
    <AppSelect v-model="actor" label="Hecho por" :options="people" filterable />
    <AppSelect v-model="typeModel" label="Acción" :options="types" multiple placeholder="Todas" />
    <AppTextField
      v-if="tab === 'incidents'"
      v-model="code"
      label="Incidente"
      type="search"
      placeholder="INC-000123"
      :maxlength="20"
      :error="incidentError"
    />
    <template v-else>
      <AppSelect v-model="target" label="Persona afectada" :options="people" filterable />
      <AppSelect v-model="role" label="Rol afectado" :options="roles" />
    </template>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.audit-filter-fields {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

@include laptop {
  .audit-filter-fields {
    flex-flow: row wrap;
    gap: $space-3;

    > * {
      width: $filter-field-width;
    }
  }
}
</style>
