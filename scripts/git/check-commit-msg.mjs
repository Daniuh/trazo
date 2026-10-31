// Valida que el mensaje de commit siga Conventional Commits: tipo(ámbito): descripción.
// Documentado en docs/operacion/flujo-de-trabajo.md
import { readFileSync } from 'node:fs';

const TYPES = ['feat', 'fix', 'docs', 'style', 'refactor', 'perf', 'test', 'build', 'ci', 'chore', 'revert'];
const PATTERN = new RegExp(`^(${TYPES.join('|')})(\\([a-z0-9-]+\\))?!?: \\S.{0,99}$`);

const firstLine = readFileSync(process.argv[2], 'utf8').split('\n')[0].trim();

// Los merges automáticos de Git no se validan
if (firstLine.startsWith('Merge ') || PATTERN.test(firstLine)) {
  process.exit(0);
}

console.error(`
El mensaje del commit no sigue el formato acordado:
  "${firstLine}"

Formato: tipo(ámbito): descripción en español (máximo 100 caracteres)
Tipos:   ${TYPES.join(', ')}
Ejemplo: feat(incidents): agregar filtro por rango de vencimiento
`);
process.exit(1);
