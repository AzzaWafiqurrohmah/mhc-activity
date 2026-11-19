<?php

// app/Models/Reward.php
namespace App\Models;

use App\StockStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    protected $fillable = ['brand', 'type', 'stock', 'created_by', 'updated_by'];

    public function stockIns(): HasMany  { return $this->hasMany(RewardStockIn::class); }
    public function stockOuts(): HasMany { return $this->hasMany(RewardStockOut::class); }

    public function status(): StockStatus { return StockStatus::forStock($this->stock); }

    public function label(): string { return "{$this->brand} {$this->type}"; }

    /** Every word must match brand or type. */
    public function scopeSearch(Builder $query, string $search): void
    {
        foreach (preg_split('/\s+/', $search) as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';
            $query->where(fn ($q) => $q->where('brand', 'like', $like)->orWhere('type', 'like', $like));
        }
    }

    public function scopeNeedsRestock(Builder $query): void
    {
        $query->where('stock', '<=', StockStatus::LOW_THRESHOLD);
    }

    public function scopeMatching(Builder $query, string $brand = '', string $type = ''): void
    {
        $query
            ->when($brand !== '', fn ($q) => $q->where('brand', $brand))
            ->when($type !== '', fn ($q) => $q->where('type', $type));
    }
}
