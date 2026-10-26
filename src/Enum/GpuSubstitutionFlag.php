<?php

namespace App\Enum;

/**
 * Bitfeld-Flags für die "Art" der Stundenplanänderung in Untis (GPU014).
 */
enum GpuSubstitutionFlag: int
{
    case Unused             = -1;
    case Cancellation        = 0x0;            // Bit  0 = Entfall (Hinweis: 0x0 bedeutet technisch "kein Bit gesetzt")
    case Supervision         = 0x1;            // Bit  1 = Betreuung
    case SpecialDuty         = 0x2;            // Bit  2 = Sondereinsatz
    case ShiftedFrom         = 0x4;            // Bit  3 = Wegverlegung
    case Release             = 0x8;            // Bit  4 = Freisetzung
    case PlusAsStandIn       = 0x10;           // Bit  5 = Plus als Vertreter
    case PartialStandIn      = 0x20;           // Bit  6 = Teilvertretung
    case ShiftedTo           = 0x40;           // Bit  7 = Hinverlegung
    case RoomExchange        = 0x8000;         // Bit 16 = Raumvertretung
    case SupervisionExchange = 0x10000;        // Bit 17 = Pausenaufsichtsvertretung
    case NoLesson            = 0x20000;        // Bit 18 = Stunde ist unterrichtsfrei
    case DoNotPrintFlag      = 0x80000;        // Bit 20 = Kennzeichen nicht drucken
    case NewFlag             = 0x1000000;      // Bit 21 = Kennzeichen neu

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
