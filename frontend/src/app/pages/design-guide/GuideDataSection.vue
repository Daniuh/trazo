<!-- Guía visual: datos y lectura (tabla con orden y selección, paginación, los tres gráficos, ícono de información,
     menú de opciones, bloque para copiar y código QR). Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { ref } from 'vue'
import AppActionMenu from '@/shared/ui/AppActionMenu.vue'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppCopyBlock from '@/shared/ui/AppCopyBlock.vue'
import AppDataTable from '@/shared/ui/AppDataTable.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppPagination from '@/shared/ui/AppPagination.vue'
import AppQrCode from '@/shared/ui/AppQrCode.vue'
import AppBarList from '@/shared/ui/charts/AppBarList.vue'
import AppColumnChart from '@/shared/ui/charts/AppColumnChart.vue'
import AppDonutChart from '@/shared/ui/charts/AppDonutChart.vue'
import type { DataColumn } from '@/shared/ui/dataTable'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'

const { notify } = useFeedback()

const columns: DataColumn[] = [
  { key: 'code', label: 'Código', sortable: true },
  { key: 'title', label: 'Título', emphasis: 'grow' },
  { key: 'status', label: 'Estado' },
]
const rows = [
  { code: 'INC-000123', title: 'No carga el módulo de facturación', status: 'Abierto' },
  { code: 'INC-000124', title: 'Impresora del piso 3 sin tóner', status: 'En progreso' },
]
const selected = ref<string | null>('INC-000123')
const descending = ref(true)
const page = ref(2)

const byStatus = [
  { key: 'open', label: 'Abierto', value: 12, tone: 'blue' as const },
  { key: 'progress', label: 'En progreso', value: 7, tone: 'orange' as const, detail: '2 vencidos' },
  { key: 'resolved', label: 'Resuelto', value: 21, tone: 'green' as const },
]
const trend = ['lun', 'mar', 'mié', 'jue', 'vie'].map((label, index) => ({
  key: label,
  label,
  values: { created: 4 + index, resolved: 3 + ((index * 2) % 5) },
}))
const series = [
  { key: 'created', label: 'Creados', tone: 'blue' as const },
  { key: 'resolved', label: 'Resueltos', tone: 'green' as const },
]
</script>

<template>
  <AppCard title="Tabla y paginación">
    <AppDataTable
      :columns="columns"
      :rows="rows"
      :row-key="(row) => row.code"
      :row-label="(row) => `${row.code}: ${row.title}`"
      sort="code"
      :descending="descending"
      :selected-key="selected"
      @open="selected = $event"
      @sort="(_, value) => (descending = value)"
    >
      <template #cell-status="{ row }">
        <AppBadge :tone="row.status === 'Abierto' ? 'blue' : 'orange'">{{ row.status }}</AppBadge>
      </template>
    </AppDataTable>
    <AppPagination
      :page="page"
      :last-page="27"
      :total="534"
      :per-page="20"
      :per-page-options="[20, 50, 100]"
      @update:page="page = $event"
    />
  </AppCard>

  <div class="guide-charts">
    <AppCard title="Barras"><AppBarList :items="byStatus" label="Incidentes por estado" /></AppCard>
    <AppCard title="Dona">
      <AppDonutChart :items="byStatus" label="Incidentes por estado" total-label="incidentes" />
    </AppCard>
    <AppCard title="Columnas">
      <AppColumnChart :points="trend" :series="series" label="Creados y resueltos por día" />
    </AppCard>
  </div>

  <AppCard title="Ayudas y acciones">
    <div class="guide-row">
      <span class="guide-row__item">
        Prioridad
        <AppInfoTip text="Define qué tan urgente es. Si no indicas vencimiento, se calcula con la prioridad." />
      </span>
      <AppButton variant="secondary" aria-label="Más acciones">
        <AppIcon name="more" />
        <AppActionMenu
          label="Más acciones"
          :items="[
            { key: 'edit', label: 'Editar', icon: 'edit' },
            { key: 'off', label: 'No disponible', disabled: true },
            { key: 'delete', label: 'Eliminar', icon: 'trash', danger: true, separated: true },
          ]"
          @select="notify(`Elegiste: ${$event}`)"
        />
      </AppButton>
    </div>
    <div class="guide-pair">
      <AppCopyBlock value="JBSWY3DPEHPK3PXP" label="Clave para escribir a mano" display="JBSW Y3DP EHPK 3PXP" />
      <AppQrCode value="otpauth://totp/Trazo:demo@trazo.test?secret=JBSWY3DPEHPK3PXP" label="Código QR de ejemplo" />
    </div>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.guide-charts,
.guide-pair {
  display: grid;
  gap: $space-4;
}

.guide-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-4;
  margin-bottom: $space-4;
}

.guide-row__item {
  display: inline-flex;
  align-items: center;
  gap: $space-1;
}

@include laptop {
  .guide-charts {
    grid-template-columns: repeat(3, minmax(0, 1fr));

    // Cada gráfico con su alto propio: estirados al alto de la fila, las columnas crecen sin fin
    align-items: start;
  }

  .guide-pair {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
  }
}
</style>
