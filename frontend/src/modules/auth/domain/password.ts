// Requisitos de una contraseña nueva, para mostrarlos mientras se escribe. Solo ayudan a la persona: la API vuelve a
// validar todo, y que no esté filtrada solo lo sabe ella. Coinciden con PasswordPolicy del backend.
// Documentado en docs/frontend/auth.md

export const PASSWORD_MIN_LENGTH = 12
export const PASSWORD_MAX_LENGTH = 128

export interface PasswordRequirement {
  key: 'length' | 'match' | 'breached'
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
    { key: 'breached', label: 'No es una contraseña conocida ni filtrada (lo revisamos al guardar)', met: null },
  ]
}
