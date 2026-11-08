<?php

namespace App;

enum Shift: string 
{ 
    case Morning = 'morning'; 
    case Afternoon = 'afternoon'; 

    public function label(): string
    {
        return match ($this) {
            self::Morning   => 'Pagi',
            self::Afternoon => 'Siang',
        };
    }
}
