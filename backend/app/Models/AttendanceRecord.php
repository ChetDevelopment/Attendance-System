<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'session_id',
        'submitted_by',
        'attendance_date',
        'status',
        'check_in_time',
        'recorded_at',
        'justification_status',
        'comment',
        'is_locked',
        'location',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'recorded_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function absenceComments()
    {
        return $this->hasMany(AbsenceComment::class);
    }

    /**
     * Get the attendance record that owns this record.
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
