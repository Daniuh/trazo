// Busca secretos en los cambios preparados para el commit, con la misma imagen de Gitleaks que usa el CI.
// Corre en Docker para no exigir instalar Gitleaks. Documentado en docs/operacion/integracion-continua.md
import { spawnSync } from 'node:child_process';

const GITLEAKS_IMAGE = 'ghcr.io/gitleaks/gitleaks:v8.30.1';

const result = spawnSync(
  'docker',
  ['run', '--rm', '-v', `${process.cwd()}:/repo`, GITLEAKS_IMAGE, 'git', '/repo', '--staged', '--no-banner', '--redact'],
  { stdio: 'inherit' },
);

if (result.error) {
  console.error('No se pudo ejecutar Docker para buscar secretos. Revisa que Docker Desktop esté encendido.');
  process.exit(1);
}

process.exit(result.status ?? 1);
