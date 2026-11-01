<!-- Auditoría global: una pestaña por registro (incidentes y administración), cada una con su permiso. Monitor: tabla
     con el detalle al lado. Portátil: tabla y el detalle en un panel a la derecha. Tablet y celular: tarjetas y el
     detalle en un panel inferior. La lista avanza con "Cargar más". Documentado en docs/frontend/audit.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { fieldMessagesFor } from '@/shared/errors'
import AppButton from '@/shared/ui/AppButton.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppSheet from '@/shared/ui/AppSheet.vue'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { auditLocation } from '../application/auditLink'
import { useAuditCatalog, useAuditList } from '../application/useAudit'
import { activeAuditFilterCount, EMPTY_CRITERIA, type AuditTab } from '../domain/audit'
import AuditCards from './list/AuditCards.vue'
import AuditDetail from './list/AuditDetail.vue'
import AuditTable from './list/AuditTable.vue'
import AuditToolbar from './list/AuditToolbar.vue'

const props = defineProps<{ tab: AuditTab }>()

const router = useRouter()
const session = useSessionStore()
const { isLaptopUp, isDesktop } = useViewport()

const list = useAuditList(props.tab)
const { criteria, items, meta, next, loading, loadingMore, error } = list
const { catalog, error: catalogError, load: loadCatalog } = useAuditCatalog()

const tabs = computed(() =>
  [
    { label: 'Incidentes', value: 'incidents' as const, permission: 'audit.incidents.view' },
    { label: 'Administración', value: 'admin' as const, permission: 'audit.admin.view' },
  ].filter((option) => session.can(option.permission)),
)
const tabModel = computed({
  get: () => props.tab,
  set: (value: AuditTab) => void router.push(auditLocation(value)),
})

const selectedId = ref<number | null>(null)
const selected = computed(() => items.value.find((entry) => entry.id === selectedId.value) ?? null)
const besideTable = computed(() => isDesktop.value && selected.value !== null)
const sheetOpen = computed({
  get: () => !isDesktop.value && selected.value !== null,
  set: (open: boolean) => {
    if (!open) selectedId.value = null
  },
})

const filtered = computed(() => activeAuditFilterCount(criteria.value) > 0)
// Un código de incidente mal escrito se muestra junto a su campo, no como falla de la lista
const incidentError = computed(() => (error.value ? fieldMessagesFor(error.value)['filter.incident'] : undefined))

const shortDate = new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', year: 'numeric' })
const periodText = computed(() =>
  meta.value
    ? `Del ${shortDate.format(new Date(meta.value.from))} al ${shortDate.format(new Date(meta.value.to))}`
    : '',
)

function filterByActor(id: string): void {
  selectedId.value = null
  list.update({ actor: id })
}
</script>

<template>
  <div class="audit-page">
    <header class="audit-page__header">
      <h1 class="audit-page__title">Auditoría</h1>
      <AppSegmented v-if="tabs.length > 1" v-model="tabModel" label="Registro" :options="tabs" />
    </header>

    <AuditToolbar
      :tab="tab"
      :criteria="criteria"
      :catalog="catalog"
      :incident-error="incidentError"
      @change="list.update"
    />
    <p v-if="periodText" class="audit-page__period">
      <AppIcon name="calendar" size="sm" />
      {{ periodText }}
    </p>

    <div class="audit-page__body" :class="{ 'audit-page__body--detail': besideTable }">
      <section class="audit-page__results" :aria-busy="loading" aria-label="Registros">
        <AppErrorState v-if="catalogError" :error="catalogError" @retry="loadCatalog" />
        <AppErrorState v-else-if="error && !incidentError" :error="error" @retry="list.load" />
        <AppEmptyState v-else-if="incidentError" title="Revisa el código del incidente" :description="incidentError">
          <AppButton variant="secondary" @click="list.update({ target: null })">Quitar el código</AppButton>
        </AppEmptyState>
        <AppSkeleton v-else-if="loading && items.length === 0" :lines="8" />
        <AppEmptyState
          v-else-if="items.length === 0 && filtered"
          title="No hay registros con estos filtros"
          description="Prueba con otras fechas u otros filtros."
        >
          <AppButton variant="secondary" @click="list.update(EMPTY_CRITERIA)">Limpiar filtros</AppButton>
        </AppEmptyState>
        <AppEmptyState
          v-else-if="items.length === 0"
          title="No hay registros en los últimos 30 días"
          description="Elige un rango de fechas más amplio para ver los anteriores."
        />

        <template v-else>
          <AuditTable
            v-if="isLaptopUp"
            :items="items"
            :catalog="catalog"
            :selected-id="besideTable ? selectedId : null"
            :compact="besideTable"
            @open="selectedId = $event"
          />
          <AuditCards v-else :items="items" :catalog="catalog" @open="selectedId = $event" />

          <div v-if="next" class="audit-page__more">
            <AppButton variant="secondary" :loading="loadingMore" @click="list.loadMore">Cargar más</AppButton>
          </div>
          <p v-else class="audit-page__end">No hay más registros en este rango.</p>
        </template>
      </section>

      <aside v-if="besideTable && selected" class="audit-page__detail" aria-label="Detalle del registro">
        <header class="audit-page__detail-header">
          <h2 class="audit-page__detail-title">{{ selected.type.name }}</h2>
          <button class="audit-page__close" type="button" aria-label="Cerrar detalle" @click="selectedId = null">
            <AppIcon name="close" />
          </button>
        </header>
        <AuditDetail :entry="selected" :catalog="catalog" @actor="filterByActor" />
      </aside>
    </div>

    <AppSheet v-model:open="sheetOpen" :title="selected?.type.name ?? ''">
      <AuditDetail v-if="selected" :entry="selected" :catalog="catalog" @actor="filterByActor" />
    </AppSheet>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.audit-page,
.audit-page__results {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.audit-page__more {
  display: flex;
  justify-content: center;
}

.audit-page__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
}

.audit-page__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.audit-page__period,
.audit-page__end {
  display: flex;
  align-items: center;
  gap: $space-2;
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

.audit-page__end {
  justify-content: center;
}

// Mientras llega la lista nueva, la actual se atenúa en lugar de desaparecer
.audit-page__results[aria-busy='true'] {
  opacity: 0.6;
  transition: opacity $duration-base;
}

.audit-page__detail {
  display: flex;
  flex-direction: column;
  gap: $space-4;
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.audit-page__detail-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: $space-3;
}

.audit-page__detail-title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

.audit-page__close {
  display: inline-flex;
  padding: $space-1;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text-secondary;
  cursor: pointer;

  &:hover {
    color: $color-text;
  }
}

@include laptop {
  .audit-page__title {
    font-size: $font-size-2xl;
  }
}

// En monitor, con un registro elegido: tabla a la izquierda y detalle a la derecha
@include desktop {
  .audit-page__body--detail {
    display: grid;
    grid-template-columns: minmax(0, 1fr) $preview-width;
    align-items: start;
    gap: $space-4;
  }
}
</style>
