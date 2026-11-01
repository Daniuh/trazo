<?php

declare(strict_types=1);

// Textos y repartos de los incidentes de demostración: realistas, en español y variados.
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders\Support;

final class IncidentSamples
{
    private const SUBJECTS = [
        'La impresora del piso 3', 'El correo corporativo', 'La VPN', 'El módulo de facturación',
        'El portátil asignado', 'La red wifi de la sala de juntas', 'El sistema de nómina', 'El acceso al CRM',
        'La pantalla del puesto 12', 'El teléfono IP de recepción', 'El escáner de documentos', 'La intranet',
        'El servidor de archivos', 'La aplicación de inventario', 'El calendario compartido', 'La cámara de la sala 2',
    ];

    private const PROBLEMS = [
        'no enciende', 'no responde', 'se desconecta cada pocos minutos', 'muestra un error al iniciar',
        'va muy lento', 'no deja iniciar sesión', 'no sincroniza los cambios', 'rechaza la contraseña',
        'se congela al abrir archivos grandes', 'pierde la configuración al reiniciar',
    ];

    private const DETAILS = [
        'Pasa desde esta mañana y afecta a todo el equipo.', 'Ya se reinició el equipo y el problema sigue.',
        'Solo ocurre con algunos usuarios del área.', 'Sucede de forma intermitente, sobre todo en la tarde.',
        'Hay clientes esperando una respuesta.', 'Empezó después de la última actualización.',
        'Se probó desde otro equipo y pasa lo mismo.', 'No hay un mensaje de error claro en pantalla.',
    ];

    /** Peso de cada estado y prioridad en los datos de demostración: entre los que siguen abiertos y entre los ya atendidos */
    public const PENDING_WEIGHTS = ['open' => 40, 'in_progress' => 40, 'on_hold' => 20];

    public const FINISHED_WEIGHTS = ['resolved' => 20, 'closed' => 65, 'cancelled' => 15];

    public const PRIORITY_WEIGHTS = ['low' => 25, 'medium' => 40, 'high' => 25, 'critical' => 10];

    public static function title(): string
    {
        return DemoRandom::randomElement(self::SUBJECTS).' '.DemoRandom::randomElement(self::PROBLEMS);
    }

    public static function description(): string
    {
        return implode(' ', DemoRandom::randomElements(self::DETAILS, DemoRandom::numberBetween(1, 3)));
    }

    /**
     * Elige una clave según su peso.
     *
     * @param  array<string, int>  $weights
     */
    public static function weighted(array $weights): string
    {
        $pick = DemoRandom::numberBetween(1, array_sum($weights));
        foreach ($weights as $key => $weight) {
            $pick -= $weight;
            if ($pick <= 0) {
                return $key;
            }
        }

        return (string) array_key_first($weights);
    }
}
