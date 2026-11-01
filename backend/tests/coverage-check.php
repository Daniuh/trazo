<?php

declare(strict_types=1);

// Revisa la cobertura del reporte Clover contra los umbrales aprobados: 90 % en las capas Domain y Application de
// todos los módulos, y 80 % en general. Si no se alcanzan, termina con error (y el CI falla).
// Uso: php tests/coverage-check.php storage/coverage/clover.xml. Documentado en docs/calidad/estrategia-de-pruebas.md

const CORE_MINIMUM = 90.0;
const GLOBAL_MINIMUM = 80.0;
const CORE_LAYERS = '#/app/Modules/[^/]+/(Domain|Application)/#';

$path = $argv[1] ?? 'storage/coverage/clover.xml';
$xml = @simplexml_load_file($path);
if ($xml === false) {
    fwrite(STDERR, "No se encontró el reporte de cobertura en {$path}.\n");
    exit(1);
}

$totals = ['core' => [0, 0], 'all' => [0, 0]];
$weakest = [];
foreach ($xml->xpath('//file') ?: [] as $file) {
    $name = str_replace('\\', '/', (string) $file['name']);
    $metrics = $file->metrics;
    $statements = (int) $metrics['statements'];
    $covered = (int) $metrics['coveredstatements'];
    if ($statements === 0) {
        continue;
    }

    $totals['all'][0] += $covered;
    $totals['all'][1] += $statements;
    if (preg_match(CORE_LAYERS, $name) === 1) {
        $totals['core'][0] += $covered;
        $totals['core'][1] += $statements;
        $weakest[$name] = $covered / $statements * 100;
    }
}

$percent = fn (array $pair): float => $pair[1] === 0 ? 100.0 : round($pair[0] / $pair[1] * 100, 1);
$core = $percent($totals['core']);
$all = $percent($totals['all']);

printf("Dominio y aplicación: %.1f %% (mínimo %.0f %%)\n", $core, CORE_MINIMUM);
printf("General:              %.1f %% (mínimo %.0f %%)\n", $all, GLOBAL_MINIMUM);

asort($weakest);
$below = array_filter($weakest, fn (float $value): bool => $value < CORE_MINIMUM);
if ($below !== []) {
    echo "\nArchivos de dominio o aplicación por debajo del 90 %:\n";
    foreach (array_slice($below, 0, 15, true) as $name => $value) {
        printf("  %5.1f %%  %s\n", $value, preg_replace('#^.*/app/#', 'app/', $name));
    }
}

exit($core >= CORE_MINIMUM && $all >= GLOBAL_MINIMUM ? 0 : 1);
