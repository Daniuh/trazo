// Requisitos de una contraseña nueva, para mostrarlos mientras se escribe. Solo ayudan a la persona: la API vuelve a
// validar todo, y que no sea predecible ni esté filtrada solo lo sabe ella (sus reglas no se copian aquí). El largo
// coincide con PasswordPolicy del backend.
// Documentado en docs/frontend/auth.md

export const PASSWORD_MIN_LENGTH = 12
export const PASSWORD_MAX_LENGTH = 128

export interface PasswordRequirement {
  key: 'length' | 'match' | 'patterns' | 'breached'
  label: string
  // null: no se puede saber hasta guardar
  met: boolean | null
}

export function passwordRequirements(password: string, confirmation: string): PasswordRequirement[] {
  return [
    {
      key: 'length',
      label: `Entre ${PASSWORD_MIN_LENGTH} y ${PASSWORD_MAX_LENGTH} caracteres`,
      met: password.length >= PASSWORD_MIN_LENGTH && password.length <= PASSWORD_MAX_LENGTH,
    },
    { key: 'match', label: 'Las dos contraseñas coinciden', met: password !== '' && password === confirmation },
    {
      key: 'patterns',
      label: 'Sin repeticiones, secuencias ni tus datos personales (lo revisamos al guardar)',
      met: null,
    },
    { key: 'breached', label: 'No es una contraseña conocida ni filtrada (lo revisamos al guardar)', met: null },
  ]
}
