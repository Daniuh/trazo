<!-- Flujo de estados: las etapas del camino conectadas, cada una con su cantidad, y aparte las salidas sin resolverse
     (Cancelado). Va en columna y pasa a fila cuando el widget es ancho (según su propio ancho, no el de la pantalla).
     Cada etapa lleva al listado; su nombre accesible es el texto que se ve (nombre y cantidad). Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { incidentListLocation } from '@/modules/incidents/application/listLink'
import { statusTone } from '@/modules/incidents/presentation/incidentTones'
import { formatNumber } from '@/shared/format/numbers'
import AppIcon from '@/shared/ui/AppIcon.vue'
import type { StatusFlowData } from '../../domain/dashboard'

defineProps<{ data: StatusFlowData }>()
</script>

<template>
  <div class="flow">
    <ol class="flow__stages" aria-label="Etapas del flujo">
      <li v-for="(stage, index) in data.stages" :key="stage.code" class="flow__step">
        <RouterLink class="flow__stage" :to="incidentListLocation(stage.filter)">
          <span class="flow__mark" :class="`flow__mark--${statusTone(stage.code)}`" aria-hidden="true" />
          <span class="flow__name">{{ stage.name }}</span>
          <span class="flow__count">{{ formatNumber(stage.count) }}</span>
        </RouterLink>
        <AppIcon v-if="index < data.stages.length - 1" class="flow__arrow" name="chevron-down" size="sm" />
      </li>
    </ol>

    <p v-if="data.exits.length > 0" class="flow__exits">
      <span>Salen sin resolverse:</span>
      <RouterLink
        v-for="exit in data.exits"
        :key="exit.code"
        class="flow__exit"
        :to="incidentListLocation(exit.filter)"
      >
        <span class="flow__mark" :class="`flow__mark--${statusTone(exit.code)}`" aria-hidden="true" />
        {{ exit.name }} <strong>{{ formatNumber(exit.count) }}</strong>
      </RouterLink>
    </p>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// El widget decide la forma según su propio ancho
.flow {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  container-type: inline-size;
}

.flow__stages {
  display: flex;
  flex-direction: column;
  margin: 0;
  padding: 0;
  list-style: none;
}

.flow__step {
  display: flex;
  flex-direction: column;
  align-items: stretch;
}

.flow__stage {
  display: flex;
  align-items: center;
  gap: $space-2;
  padding: $space-1 $space-3;
  border: 1px solid $color-border;
  border-radius: $radius-control;
  background: $color-page;
  color: $color-text;
  font-size: $font-size-sm;
  text-decoration: none;

  &:hover {
    border-color: $color-border-strong;
  }
}

.flow__mark {
  flex: none;
  width: $space-2;
  height: $space-2;
  border-radius: 50%;
}

@each $name, $fill in $chart-fills {
  .flow__mark--#{$name} {
    background: $fill;
  }
}

.flow__name {
  flex: 1;
}

.flow__count {
  font-variant-numeric: tabular-nums;
  font-weight: $font-weight-semibold;
}

.flow__arrow {
  align-self: center;
  color: $color-muted;
}

.flow__exits {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2;
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.flow__exit {
  display: inline-flex;
  align-items: center;
  gap: $space-1;
  color: $color-text;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

// Con espacio de sobra, las etapas van en fila con flechas hacia la derecha
@container (min-width: #{$flow-row-from}) {
  .flow__stages {
    flex-direction: row;
  }

  .flow__step {
    flex: 1;
    flex-direction: row;
    align-items: center;
    min-width: $flow-stage-min-width;
  }

  .flow__stage {
    flex: 1;
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
    min-width: 0;
    padding: $space-2;
  }

  .flow__mark {
    order: -1;
  }

  .flow__count {
    font-size: $font-size-lg;
  }

  .flow__arrow {
    transform: rotate(-90deg);
  }
}
</style>
