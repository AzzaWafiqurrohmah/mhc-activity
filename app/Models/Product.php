<?php

// app/Models/Product.php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['barcode', 'name', 'division_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function division() { return $this->belongsTo(Division::class); }

    /** Setiap kata harus cocok di barcode, nama, atau nama divisi. */
    public function scopeSearch(Builder $query, string $search): void
    {
        foreach (preg_split('/\s+/', $search) as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';

            $query->where(fn ($q) => $q
                ->where('barcode', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhereHas('division', fn ($d) => $d->where('name', 'like', $like)));
        }
    }
}