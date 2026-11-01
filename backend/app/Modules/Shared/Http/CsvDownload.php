<?php

declare(strict_types=1);

// Descarga en CSV escrita por partes, pensada para Excel en español: UTF-8 con BOM (tildes bien), punto y coma como
// separador y protección contra inyección de fórmulas. Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Http;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvDownload
{
    private const BOM = "\xEF\xBB\xBF";

    private const SEPARATOR = ';';

    // Excel ejecuta como fórmula una celda que empieza así (OWASP: CSV injection)
    private const FORMULA_START = ['=', '+', '-', '@', "\t", "\r", "\n"];

    /**
     * @param  list<string>  $headers
     * @param  iterable<list<string>>  $rows  Se leen mientras se escribe: nunca están todas en memoria
     */
    public static function response(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }
            fwrite($output, self::BOM);
            self::write($output, $headers);
            foreach ($rows as $row) {
                self::write($output, $row);
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    // Una celda que Excel leería como fórmula se guarda como texto anteponiendo un apóstrofo
    public static function cell(string $value): string
    {
        return $value !== '' && in_array($value[0], self::FORMULA_START, true) ? "'{$value}" : $value;
    }

    /**
     * @param  resource  $output
     * @param  list<string>  $cells
     */
    private static function write($output, array $cells): void
    {
        fputcsv($output, array_map(self::cell(...), $cells), self::SEPARATOR, '"', '', "\r\n");
    }
}
