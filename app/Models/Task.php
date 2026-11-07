<?php

namespace App\Models;

use App\TaskType;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['division_id', 'name', 'type', 'sort_order', 'is_active'];
    protected function casts(): array { return ['type' => TaskType::class, 'is_active' => 'boolean']; }
    public function division() { return $this->belongsTo(Division::class); }
}
