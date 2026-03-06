<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceRecord;
use App\Models\User;

class AbsenceComment extends Model
{
    use HasFactory;
     protected $fillable = [
        'attendance_record_id',
        'reason',
        'excuse_status',
        'commented_by',
    ];

    // This comment belongs to an attendance record
    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    // This comment belongs to a user (Education Team member)
    public function user()
    {
        return $this->belongsTo(User::class, 'commented_by');
    }
}
