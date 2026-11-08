<?php

namespace App;

enum LogStatus: string 
{ 
    case Present = 'present'; 
    case Off = 'off'; 

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Masuk',
            self::Off     => 'Off',
        };
    }
}
