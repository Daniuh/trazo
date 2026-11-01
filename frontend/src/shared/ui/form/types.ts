// Tipos compartidos por los campos de formulario.
export interface SelectOption<V> {
  label: string
  value: V
}

// Opción de AppRadioList: nombre y, debajo, una línea que la explica
export interface RadioOption {
  value: string
  label: string
  description?: string
  disabled?: boolean
}
