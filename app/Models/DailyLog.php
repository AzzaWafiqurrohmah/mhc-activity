<?php

namespace App\Models;

use App\LogStatus;
use App\Shift;
use Illuminate\Database\Eloquent\Model;

class DailyLog extends Model
{
    protected $fillable = ['user_id','division_id','log_date','status','shift','other_note'];
    protected function casts(): array {
        return ['log_date' => 'date', 'status' => LogStatus::class, 'shift' => Shift::class];
    }
    public function user() { return $this->belongsTo(User::class); }
    public function division() { return $this->belongsTo(Division::class); }
    public function tasks() { return $this->belongsToMany(Task::class, 'daily_log_task'); }
}
