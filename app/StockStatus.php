<?php

namespace App;

enum StockStatus: string
{
    case Safe = 'safe';
    case Low  = 'low';
    case Out  = 'out';

    public const LOW_THRESHOLD = 5;

    public static function forStock(int $stock): self
    {
        return match (true) {
            $stock <= 0                  => self::Out,
            $stock <= self::LOW_THRESHOLD => self::Low,
            default                      => self::Safe,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Safe => 'Aman',
            self::Low  => 'Menipis',
            self::Out  => 'Habis',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Safe => 'badge-green',
            self::Low  => 'badge-amber',
            self::Out  => 'badge-red',
        };
    }
}