<?php

declare(strict_types=1);

// Widgets que el sistema sabe calcular. El catálogo (dashboard_widgets) solo ofrece los que aparecen aquí,
// así una fila sin implementación nunca llega a la pantalla. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Domain;

enum WidgetCode: string
{
    // Diseño centrado en el riesgo (ADR-0081)
    case OperationPulse = 'operation_pulse';
    case RiskMap = 'risk_map';
    case StatusFlow = 'status_flow';
    case BacklogBalance = 'backlog_balance';
    case TotalIncidents = 'total_incidents';
    case PendingIncidents = 'pending_incidents';
    case OverdueIncidents = 'overdue_incidents';
    case UnassignedIncidents = 'unassigned_incidents';
    case IncidentsByStatus = 'incidents_by_status';
    case IncidentsByPriority = 'incidents_by_priority';
    case CreatedVsResolved = 'created_vs_resolved';
    case DueSoon = 'due_soon';
    case AssignedToMe = 'assigned_to_me';
    case WorkloadByAgent = 'workload_by_agent';
}
