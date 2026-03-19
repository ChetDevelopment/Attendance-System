<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceFollowUp extends Model
{
    protected $fillable = [
        'attendance_record_id',
        'updated_by',
        'reason',
        'comment',
        'note',
        'status',
        'resolved',
        'is_excused',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'is_excused' => 'boolean',
    ];
}
