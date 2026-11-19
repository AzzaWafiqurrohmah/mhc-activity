<?php

// app/Models/RewardStockIn.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardStockIn extends Model
{
    protected $fillable = ['reward_id', 'received_on', 'quantity', 'notes', 'created_by', 'created_by_name'];

    protected function casts(): array
    {
        return ['received_on' => 'date', 'quantity' => 'integer'];
    }

    public function reward(): BelongsTo { return $this->belongsTo(Reward::class); }
}
