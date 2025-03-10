<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OverTimeSetting extends Model
{
    use HasFactory;

    protected $table = 'over_time_settings';

    protected $fillable = [
        'title','max_daily_ot_hours', 'max_weekly_ot_hours', 'max_monthly_ot_hours', 'valid_after_hour', 'overtime_pay_rate', 'is_active','pay_type','pay_percent'
    ];

    public function otEmployees(): HasMany
    {
        return $this->hasMany(OverTimeEmployee::class, 'over_time_setting_id', 'id');
    }
}
