<?php

declare(strict_types=1);

// Dónde sigue la lista: la fecha y el id del último registro entregado. El orden es (fecha, id) de mayor a menor, así
// que la siguiente tanda empieza justo después, sin importar cuántos registros lleguen mientras tanto.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Domain;

use DateTimeImmutable;
use DateTimeZone;

final readonly class AuditCursor
{
    private const FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        public DateTimeImmutable $createdAt,
        public int $id,
    ) {}

    // Texto opaco para la URL: base64 sin relleno de "fecha|id"
    public function encode(): string
    {
        $raw = $this->createdAt->setTimezone(new DateTimeZone('UTC'))->format(self::FORMAT).'|'.$this->id;

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function decode(string $value): ?self
    {
        $raw = base64_decode(strtr($value, '-_', '+/'), true);
        if ($raw === false || preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\|(\d{1,19})$/', $raw, $parts) !== 1) {
            return null;
        }

        $createdAt = DateTimeImmutable::createFromFormat(self::FORMAT, $parts[1], new DateTimeZone('UTC'));

        // PHP acomoda fechas imposibles (mes 13 pasa al año siguiente): se exige que la fecha vuelva igual
        if ($createdAt === false || $createdAt->format(self::FORMAT) !== $parts[1]) {
            return null;
        }

        return new self($createdAt, (int) $parts[2]);
    }

    public function sqlDate(): string
    {
        return $this->createdAt->setTimezone(new DateTimeZone('UTC'))->format(self::FORMAT);
    }
}
