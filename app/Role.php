<?php

namespace App;

enum Role: string 
{ 
    case Staff = 'staff'; 
    case Sm = 'sm'; 
    case Admin = 'admin'; 

    public function homeRoute(): string
    {
        return match ($this) {
            self::Staff => 'staff.dashboard',
            self::Sm    => 'sm.dashboard',
            self::Admin => 'admin.dashboard',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Sm    => 'Store Manager',
            self::Admin => 'Admin',
        };
    }
}
