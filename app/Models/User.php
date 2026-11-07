<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name','employee_code','password','role','division_id','active_from','active_until','is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return ['password' => 'hashed', 'role' => Role::class,
                'active_from' => 'date', 'active_until' => 'date', 'is_active' => 'boolean'];
    }

    public function division() { return $this->belongsTo(Division::class); }
    public function dailyLogs() { return $this->hasMany(DailyLog::class); }
}
