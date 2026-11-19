<?php

// app/Models/RewardStockOut.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardStockOut extends Model
{
    protected $fillable = [
        'given_on', 'receipt_no', 'member_id', 'member_no', 'member_name', 'phone',
        'purchase_total', 'reward_id', 'quantity', 'notes', 'created_by', 'created_by_name',
    ];

    protected function casts(): array
    {
        return ['given_on' => 'date', 'purchase_total' => 'integer', 'quantity' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(fn (self $out) => $out->phone_search = Member::normalizePhone($out->phone));
    }

    public function reward(): BelongsTo { return $this->belongsTo(Reward::class); }
    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
}