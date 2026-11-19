<?php

// database/seeders/RewardSeeder.php
namespace Database\Seeders;

use App\Models\Reward;
use App\Models\User;
use App\Role;
use App\Services\RewardStockService;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    /** [brand, type, opening stock] */
    private const REWARDS = [
        ['Kansai',      'Tumbler',   10],
        ['Kansai',      'Payung',     4],   // menipis
        ['Nippon Paint', 'Topi',     25],
        ['Dulux',       'Jas Hujan', 15],
        ['Avian',       'Handuk',     0],   // habis
        ['Mowilex',     'Tote Bag',   8],
    ];

    public function run(RewardStockService $stock): void
    {
        // The stock-in log needs a user for created_by / created_by_name.
        $user = User::where('role', Role::Staff->value)->first() ?? User::firstOrFail();

        foreach (self::REWARDS as [$brand, $type, $openingStock]) {
            // Safe to run more than once: existing rewards are left untouched.
            if (Reward::where('brand', $brand)->where('type', $type)->exists()) {
                continue;
            }

            $stock->create($brand, $type, $openingStock, $user);
        }
    }
}