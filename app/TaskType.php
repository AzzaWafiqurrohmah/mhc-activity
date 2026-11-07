<?php

namespace App;

enum TaskType: string 
{ 
    case Routine = 'routine'; 
    case Incidental = 'incidental'; 

    public function label(): string
    {
        return match ($this) {
            self::Routine    => 'Rutin',
            self::Incidental => 'Insidental',
        };
    }
}
