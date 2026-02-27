<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;

     protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'late_after_minutes',
        'is_active',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
