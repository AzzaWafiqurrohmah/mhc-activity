<?php

// app/Services/RewardStockService.php
namespace App\Services;

use App\Models\Member;
use App\Models\Reward;
use App\Models\RewardStockIn;
use App\Models\RewardStockOut;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardStockService
{
    /** Opening stock is logged as the first stock-in, so stock always equals ins minus outs. */
    public function create(string $brand, string $type, int $openingStock, User $by): Reward
    {
        return DB::transaction(function () use ($brand, $type, $openingStock, $by) {
            $reward = Reward::create([
                'brand' => $brand, 'type' => $type, 'stock' => 0, 'created_by' => $by->id,
            ]);

            if ($openingStock > 0) {
                $this->receive($reward->id, today()->toDateString(), $openingStock, 'Stok awal', $by);
            }

            return $reward->refresh();
        });
    }

    /** A negative quantity is a correction. */
    public function receive(int $rewardId, string $receivedOn, int $quantity, ?string $notes, User $by): RewardStockIn
    {
        return DB::transaction(function () use ($rewardId, $receivedOn, $quantity, $notes, $by) {
            $reward = Reward::lockForUpdate()->findOrFail($rewardId);

            if ($reward->stock + $quantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok {$reward->label()} hanya {$reward->stock}, tidak bisa dikurangi " . abs($quantity) . '.',
                ]);
            }

            $entry = RewardStockIn::create([
                'reward_id'       => $reward->id,
                'received_on'     => $receivedOn,
                'quantity'        => $quantity,
                'notes'           => $notes,
                'created_by'      => $by->id,
                'created_by_name' => $by->name,
            ]);

            $reward->increment('stock', $quantity);

            return $entry;
        });
    }

    public function release(array $data, User $by): RewardStockOut
    {
        return DB::transaction(function () use ($data, $by) {
            $reward = Reward::lockForUpdate()->findOrFail($data['reward_id']);

            if ($reward->stock < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok {$reward->label()} hanya {$reward->stock}, tidak cukup untuk {$data['quantity']}.",
                ]);
            }

            // Resolved on the server; never trust a member_id sent by the browser.
            $memberId = filled($data['member_no'] ?? null)
                ? Member::where('member_no', $data['member_no'])->value('id')
                : null;

            $entry = RewardStockOut::create([
                'given_on'        => $data['given_on'],
                'receipt_no'      => $data['receipt_no'],
                'member_id'       => $memberId,
                'member_no'       => $data['member_no'] ?? null,
                'member_name'     => $data['member_name'],
                'phone'           => $data['phone'] ?? null,
                'purchase_total'  => $data['purchase_total'],
                'reward_id'       => $reward->id,
                'quantity'        => $data['quantity'],
                'notes'           => $data['notes'] ?? null,
                'created_by'      => $by->id,
                'created_by_name' => $by->name,
            ]);

            $reward->decrement('stock', $data['quantity']);

            return $entry;
        });
    }
}