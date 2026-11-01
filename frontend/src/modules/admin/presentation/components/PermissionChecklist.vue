<!-- Permisos agrupados por módulo. Al ver: los que están marcados (y, si hay una base, los que se quitaron de ella).
     Al editar: una casilla por permiso; los que quien administra no tiene y no están marcados quedan deshabilitados,
     con el motivo en el ícono de información. Con "base" (los permisos del rol de una persona), lo que difiere lleva
     "Adicional" o "Quitado del rol". La usan la ficha de persona y la de rol. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppCheckbox from '@/shared/ui/form/AppCheckbox.vue'
import { canToggle, differenceFromRole, groupByModule } from '../../domain/permissions'
import type { PermissionOption } from '../../domain/user'

const props = defineProps<{ catalog: PermissionOption[]; editing: boolean; base?: string[] }>()
const selected = defineModel<string[]>({ required: true })

const groups = computed(() => groupByModule(props.catalog))

const difference = (code: string) => (props.base ? differenceFromRole(code, selected.value, props.base) : null)

// Al ver, solo se listan los que tiene y los que se le quitaron de la base
const visible = (code: string) => props.editing || selected.value.includes(code) || difference(code) !== null

function toggle(code: string, on: boolean): void {
  selected.value = on ? [...selected.value, code] : selected.value.filter((item) => item !== code)
}
</script>

<template>
  <div class="permission-checklist">
    <template v-for="group in groups" :key="group.module">
      <section v-if="group.items.some((permission) => visible(permission.code))">
        <h3 class="permission-checklist__module">{{ group.label }}</h3>
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

.permission-checklist__module {
  margin: 0 0 $space-2;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
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
</style>
