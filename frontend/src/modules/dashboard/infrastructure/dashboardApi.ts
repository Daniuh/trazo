// Repositorio del dashboard: diseño, métricas, guardar y restaurar.
// Documentado en docs/frontend/dashboard.md
import { http } from '@/shared/http'
import type { DashboardLayout, DashboardWidget, Metrics, TrendDays, WidgetCode } from '../domain/dashboard'
import { toLayout, toMetrics, type LayoutDto } from './mappers'

export interface MetricsPage {
  metrics: Metrics
  generatedAt: string
}

export const dashboardApi = {
  async layout(): Promise<DashboardLayout> {
    const { data } = await http.get<{ data: LayoutDto }>('/dashboard')
    return toLayout(data.data)
  },

  async metrics(codes: WidgetCode[], trendDays: TrendDays, signal?: AbortSignal): Promise<MetricsPage> {
    const { data } = await http.get<{ data: Record<string, unknown>; meta: { generated_at: string } }>(
      '/dashboard/metrics',
      { params: { widgets: codes.join(','), trend_days: trendDays }, signal },
    )
    return { metrics: toMetrics(data.data), generatedAt: data.meta.generated_at }
  },

  async save(widgets: DashboardWidget[]): Promise<DashboardLayout> {
    const payload = widgets.map(({ code, x, y, w, h, visible }) => ({ code, x, y, w, h, visible }))
    const { data } = await http.put<{ data: LayoutDto }>('/dashboard/layout', { widgets: payload })
    return toLayout(data.data)
  },

  async reset(): Promise<DashboardLayout> {
    const { data } = await http.delete<{ data: LayoutDto }>('/dashboard/layout')
    return toLayout(data.data)
  },
}
