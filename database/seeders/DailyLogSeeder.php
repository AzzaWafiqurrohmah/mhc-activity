<?php

namespace Database\Seeders;

use App\LogStatus;
use App\Models\DailyLog;
use App\Models\User;
use App\Role;
use App\Shift;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DailyLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staff = User::where('role', Role::Staff)->with('division.tasks')->get();

    foreach ($staff as $user) {
        $tasks = $user->division->tasks;

        // 30 hari terakhir, tidak termasuk hari ini
        for ($i = 30; $i >= 1; $i--) {
            $roll = random_int(1, 100);

            if ($roll <= 10) {
                continue;                       // hari kosong (lupa input)
            }

            $isOff = $roll <= 25;               // 15% off

            $log = DailyLog::create([
                'user_id'     => $user->id,
                'division_id' => $user->division_id,
                'log_date'    => now()->subDays($i)->toDateString(),
                'status'      => $isOff ? LogStatus::Off : LogStatus::Present,
                'shift'       => $isOff ? null : collect(Shift::cases())->random(),
                'other_note'  => (!$isOff && random_int(1, 100) <= 10) ? 'Bantu pindahan rak' : null,
            ]);

            if (!$isOff) {
                $log->tasks()->attach(
                    $tasks->random(random_int(2, min(4, $tasks->count())))->pluck('id')
                );
            }
        }
    }
    }
}
