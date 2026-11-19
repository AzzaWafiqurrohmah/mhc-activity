<?php

// app/Http/Controllers/Concerns/CleansFilters.php
namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait CleansFilters
{
    protected function perPageOptions(): array
    {
        return [10, 25, 50];
    }

    protected function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page');

        return in_array($perPage, $this->perPageOptions(), true) ? $perPage : 10;
    }

    protected function cleanDate(?string $value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }
}
