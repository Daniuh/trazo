// Columnas de AppDataTable. Documentado en docs/frontend/sistema-de-diseno/componentes.md

export interface DataColumn {
  key: string
  label: string
  // Columna por la que se puede ordenar; su clave es el campo de orden
  sortable?: boolean
  // El nombre queda solo para lectores de pantalla (p. ej. la columna de botones)
  hiddenLabel?: boolean
  // "secondary": texto secundario; "grow": toma el espacio libre y corta con "…"
  emphasis?: 'secondary' | 'grow'
}
