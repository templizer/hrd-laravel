<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarningResponse extends Model
{
    use HasFactory;

    protected $table = 'warning_responses';

    protected $fillable = [
        'warning_id',
        'employee_id',
        'message'
    ];

    public function warning()
    {
        return $this->belongsTo(Warning::class, 'warning_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }
}
