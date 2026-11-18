<?php

namespace App;

enum AreaType: string
{
    case Inside  = 'dalam_kota';
    case Outside = 'luar_kota';

    public function label(): string
    {
        return match ($this) {
            self::Inside  => 'Dalam kota',
            self::Outside => 'Luar kota',
        };
    }
}