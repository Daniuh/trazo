// Valida la trazabilidad entre documentación y código: los enlaces relativos de docs/ apuntan a archivos
// que existen, y los encabezados del código citan documentos que existen.
// Documentado en docs/operacion/integracion-continua.md
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';

const ROOT = resolve(import.meta.dirname, '../..');
const CODE_DIRS = ['backend/app', 'backend/bootstrap', 'backend/config', 'backend/database', 'backend/routes', 'backend/tests', 'frontend/src', 'docker', 'scripts', '.github'];
const SKIP_DIRS = new Set(['node_modules', 'vendor', 'dist', '.git']);

const MD_LINK = /\]\(([^)\s]+)\)/g;
const DOC_REFERENCE = /docs\/[\w./-]+\.md/g;

function* walk(dir) {
  if (!existsSync(dir)) return;
  for (const entry of readdirSync(dir)) {
    if (SKIP_DIRS.has(entry)) continue;
    const path = join(dir, entry);
    if (statSync(path).isDirectory()) yield* walk(path);
    else yield path;
  }
}

// Externos, anclas internas y correos no se validan aquí
const isLocal = (target) => !/^(https?:|mailto:|#)/.test(target);

const broken = [];
let checked = 0;

for (const file of walk(join(ROOT, 'docs'))) {
  if (!file.endsWith('.md')) continue;
  for (const [, target] of readFileSync(file, 'utf8').matchAll(MD_LINK)) {
    if (!isLocal(target)) continue;
    checked++;
    const path = decodeURIComponent(target.split('#')[0]);
    if (!existsSync(resolve(dirname(file), path))) broken.push(`${relative(ROOT, file)} → ${target}`);
  }
}

for (const dir of CODE_DIRS) {
  for (const file of walk(join(ROOT, dir))) {
    for (const [reference] of readFileSync(file, 'utf8').matchAll(DOC_REFERENCE)) {
      checked++;
      if (!existsSync(join(ROOT, reference))) broken.push(`${relative(ROOT, file)} → ${reference}`);
    }
  }
}

if (broken.length > 0) {
  console.error(`Hay ${broken.length} enlaces rotos:\n${broken.map((line) => `  ${line}`).join('\n')}`);
  process.exit(1);
}

console.log(`${checked} enlaces revisados, ninguno roto.`);
