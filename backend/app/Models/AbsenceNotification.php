<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'session_id',
        'attendance_record_id',
        'absence_reason',
        'absence_status',
        'comment',
        'follow_up_notes',
        'reason_submitted_by',
        'reason_submitted_at',
        'status_updated_by',
        'status_updated_at',
        'status',
    ];

    protected $casts = [
        'reason_submitted_at' => 'datetime',
        'status_updated_at' => 'datetime',
    ];

    const STATUS_PENDING = 'PENDING';
    const STATUS_EXCUSED = 'EXCUSED';
    const STATUS_UNEXCUSED = 'UNEXCUSED';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function reasonSubmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reason_submitted_by');
    }

    public function statusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }
}
