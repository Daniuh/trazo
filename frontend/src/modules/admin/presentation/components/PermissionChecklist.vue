<!-- Permisos agrupados por módulo. Al ver: los que están marcados (y, si hay una base, los que se quitaron de ella).
     Al editar: una casilla por permiso y "Todos" por módulo; los que quien administra no tiene y no están marcados quedan
     deshabilitados, con el motivo en el ícono de información. Con "base" (los permisos del rol de una persona), lo que
     difiere lleva "Adicional" o "Quitado del rol". Con `columns` (ficha del rol), los módulos van en columnas desde
     tablet y en celular se pliegan, con el conteo a la vista. La usan la ficha de persona y la de rol.
     Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed, ref, useId } from 'vue'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppCheckbox from '@/shared/ui/form/AppCheckbox.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { canToggle, differenceFromRole, groupByModule, moduleSelection, toggleModule } from '../../domain/permissions'
import type { PermissionOption } from '../../domain/user'

const props = defineProps<{ catalog: PermissionOption[]; editing: boolean; base?: string[]; columns?: boolean }>()
const selected = defineModel<string[]>({ required: true })

const id = useId()
const { isMobile } = useViewport()

const groups = computed(() =>
  groupByModule(props.catalog).map((group) => ({ ...group, summary: moduleSelection(group.items, selected.value) })),
)

const difference = (code: string) => (props.base ? differenceFromRole(code, selected.value, props.base) : null)

// Al ver, solo se listan los que tiene y los que se le quitaron de la base
const visible = (code: string) => props.editing || selected.value.includes(code) || difference(code) !== null

function toggle(code: string, on: boolean): void {
  selected.value = on ? [...selected.value, code] : selected.value.filter((item) => item !== code)
}

function toggleAll(items: PermissionOption[], on: boolean): void {
  selected.value = toggleModule(items, selected.value, on)
}

// En celular los módulos se pliegan; arrancan abiertos los que traen un permiso que no se puede tocar, para que se vea
const collapsible = computed(() => props.columns && isMobile.value)
const expanded = ref(
  new Set(groups.value.filter((group) => props.editing && group.summary.blocked).map((group) => group.module)),
)
const isOpen = (module: string) => !collapsible.value || expanded.value.has(module)

function toggleOpen(module: string): void {
  const next = new Set(expanded.value)
  if (!next.delete(module)) next.add(module)
  expanded.value = next
}
</script>

<template>
  <div class="permission-checklist" :class="{ 'permission-checklist--columns': columns }">
    <template v-for="group in groups" :key="group.module">
      <section
        v-if="group.items.some((permission) => visible(permission.code))"
        class="permission-checklist__module"
        :class="{ 'permission-checklist__module--collapsible': collapsible }"
      >
        <button
          v-if="collapsible"
          type="button"
          class="permission-checklist__toggle"
          :aria-expanded="isOpen(group.module)"
          :aria-controls="`${id}-${group.module}`"
          @click="toggleOpen(group.module)"
        >
          <span class="permission-checklist__title">{{ group.label }}</span>
          <span class="permission-checklist__count">{{ group.summary.selected }} de {{ group.summary.total }}</span>
          <AppIcon :name="isOpen(group.module) ? 'chevron-up' : 'chevron-down'" size="sm" />
        </button>
        <h3 v-else class="permission-checklist__header">
          <span class="permission-checklist__title">{{ group.label }}</span>
          <span v-if="columns" class="permission-checklist__count">
            {{ group.summary.selected }} de {{ group.summary.total }}
          </span>
        </h3>

        <div v-show="isOpen(group.module)" :id="`${id}-${group.module}`" class="permission-checklist__body">
          <AppCheckbox
            v-if="editing && columns"
            class="permission-checklist__all"
            label="Todos"
            :aria-label="`Todos los permisos de ${group.label}`"
            :model-value="group.summary.state === 'all'"
            :indeterminate="group.summary.state === 'some'"
            @update:model-value="toggleAll(group.items, $event)"
          />
          <ul class="permission-checklist__list">
            <template v-for="permission in group.items" :key="permission.code">
              <li v-if="visible(permission.code)" class="permission-checklist__item">
                <AppCheckbox
                  v-if="editing"
                  :model-value="selected.includes(permission.code)"
                  :label="permission.name"
                  :disabled="!canToggle(permission, selected)"
                  @update:model-value="toggle(permission.code, $event)"
                />
                <span
                  v-else
                  class="permission-checklist__name"
                  :class="{ 'permission-checklist__name--removed': !selected.includes(permission.code) }"
                >
                  <AppIcon :name="selected.includes(permission.code) ? 'check' : 'close'" size="sm" />
                  {{ permission.name }}
                </span>

                <AppInfoTip
                  v-if="editing && !canToggle(permission, selected)"
                  text="No tienes este permiso, así que no lo puedes conceder."
                  :label="`Por qué no se puede conceder ${permission.name}`"
                />
                <AppBadge v-if="difference(permission.code) === 'added'" tone="blue">Adicional</AppBadge>
                <AppBadge v-else-if="difference(permission.code) === 'removed'" tone="orange">Quitado del rol</AppBadge>
              </li>
            </template>
          </ul>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.permission-checklist {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.permission-checklist__header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: $space-2;
  margin: 0 0 $space-2;
  font-size: $font-size-sm;
}

.permission-checklist__title {
  font-weight: $font-weight-semibold;
}

.permission-checklist__count {
  color: $color-text-secondary;
  font-size: $font-size-xs;
  font-weight: $font-weight-regular;
  white-space: nowrap;
}

.permission-checklist__all {
  width: 100%;
  margin-bottom: $space-1;
  border-bottom: 1px solid $color-border;
  font-weight: $font-weight-medium;
}

.permission-checklist__list {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  margin: 0;
  padding: 0;
  list-style: none;
}

.permission-checklist__item {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2;
  font-size: $font-size-sm;
}

.permission-checklist__name {
  display: inline-flex;
  align-items: center;
  gap: $space-2;
}

.permission-checklist__name--removed {
  color: $color-text-secondary;
  text-decoration: line-through;
}

// Celular: cada módulo es una fila que se despliega, separadas por una línea
.permission-checklist--columns {
  gap: 0;
}

.permission-checklist__module--collapsible {
  border-bottom: 1px solid $color-border;

  .permission-checklist__body {
    padding-bottom: $space-3;
  }
}

.permission-checklist__toggle {
  display: flex;
  align-items: center;
  gap: $space-2;
  width: 100%;
  min-height: $touch-target;
  padding: 0;
  border: 0;
  background: none;
  color: inherit;
  font: inherit;
  font-size: $font-size-sm;
  text-align: left;
  cursor: pointer;

  .permission-checklist__title {
    flex: 1;
  }
}

// Tablet en adelante: los módulos fluyen en columnas sin cortarse
@include tablet {
  .permission-checklist--columns {
    display: block;
    column-count: 2;
    column-gap: $space-6;

    .permission-checklist__module {
      break-inside: avoid;
      padding-bottom: $space-5;
    }
  }
}

@include laptop {
  .permission-checklist--columns {
    column-count: 3;
  }
}
</style>
