<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $fillable = ['name', 'is_active'];
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
    public function users() { return $this->hasMany(User::class); }
    public function tasks() { return $this->hasMany(Task::class); }
}
