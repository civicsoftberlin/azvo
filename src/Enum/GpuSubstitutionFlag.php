<?php

namespace App\Enum;

/**
 * Bitfeld-Flags für die "Art" der Stundenplanänderung in Untis (GPU014).
 */
enum GpuSubstitutionFlag: int
{
    case Unused             = -1;            // Hilfsweise für nulls/leer
    case Cancellation       = 0x1;           // Bit  0 = Entfall (1)
    case Supervision        = 0x2;           // Bit  1 = Betreuung (2)
    case SpecialDuty        = 0x4;           // Bit  2 = Sondereinsatz (4)
    case ShiftedFrom        = 0x8;           // Bit  3 = Wegverlegung (8)
    case Release            = 0x10;          // Bit  4 = Freisetzung (16)
    case PlusAsStandIn      = 0x20;          // Bit  5 = Plus als Vertreter (32)
    case PartialStandIn     = 0x40;          // Bit  6 = Teilvertretung (64)
    case ShiftedTo          = 0x80;          // Bit  7 = Hinverlegung (128)
    case RoomExchange       = 0x10000;       // Bit 16 = Raumvertretung (65.536)
    case SupervisionExchange= 0x20000;       // Bit 17 = Pausenaufsichtsvertretung (131.072)
    case NoLesson           = 0x40000;       // Bit 18 = Stunde ist unterrichtsfrei (262.144)
    // Bit 19 (0x80000 = 524.288) fehlt in der Doku, wird übersprungen
    case DoNotPrintFlag     = 0x100000;      // Bit 20 = Kennzeichen nicht drucken (1.048.576)
    case NewFlag            = 0x200000;      // Bit 21 = Kennzeichen neu (2.097.152)

    public function getGermanLabel(): string
    {
        return match ($this) {
            self::Unused => "-",
            self::Cancellation => "Ausfall",
            self::Supervision => "Betreuung",
            self::SpecialDuty => "Sondereinsatz",
            self::ShiftedFrom => "Wegverlegung",
            self::Release => "Freisetzung",
            self::PlusAsStandIn => "Plus als Vertreter",
            self::PartialStandIn => "Teilvertretung",
            self::ShiftedTo => "Hinverlegung",
            self::RoomExchange => "Raumvertretung",
            self::SupervisionExchange => "Pausenaufsichtsvertretung",
            self::NoLesson => "Stunde ist unterrichtsfrei",
            self::DoNotPrintFlag => "Kennzeichen nicht drucken",
            self::NewFlag => "Kennzeichen neu",
        };
    }

    /**
     * Prüft, ob dieses spezifische Flag in einem gegebenen Bitfeld-Wert gesetzt ist.
     */
    public function isIn(int $bitfield): bool
    {
        return ($bitfield & $this->value) === $this->value;
    }

    public static function tryFromBit(?int $bit): ?self
    {
        if ($bit === null) {
            return self::tryFrom(-1);
        }
        return self::tryFrom($bit);
    }

    /**
     * Extrahiert alle gesetzten Flags aus einem gegebenen Bitfeld-Wert.
     *
     * @return list<self>
     */
    public static function getActiveFlags(int $bitfield): array
    {
        $activeFlags = [];
        foreach (self::cases() as $flag) {
            if ($flag->isIn($bitfield)) {
                $activeFlags[] = $flag->getGermanLabel();
            }
        }
        return $activeFlags;
    }

    public static function getActiveFlagsAsStringArray(array $array): array
    {
        return array_map(fn($f) => $f->name, $array);
    }
}
