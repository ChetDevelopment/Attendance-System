<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'session_id',
        'attendance_id',
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

    public function setSubmittedByAttribute($value): void
    {
        if ($value && Schema::hasColumn($this->getTable(), 'submitted_by')) {
            $this->attributes['submitted_by'] = $value;
        }

        if ($value && Schema::hasColumn($this->getTable(), 'recorded_by')) {
            $this->attributes['recorded_by'] = $value;
        }
    }

    public function getSubmittedByAttribute($value)
    {
        return $value ?? $this->attributes['recorded_by'] ?? null;
    }

    public function setRecordedByAttribute($value): void
    {
        if ($value && Schema::hasColumn($this->getTable(), 'recorded_by')) {
            $this->attributes['recorded_by'] = $value;
        }

        if ($value && Schema::hasColumn($this->getTable(), 'submitted_by')) {
            $this->attributes['submitted_by'] = $value;
        }
    }

    public function getRecordedByAttribute()
    {
        if (Schema::hasColumn($this->getTable(), 'recorded_by')) {
            return $this->attributes['recorded_by'] ?? $this->attributes['submitted_by'] ?? null;
        }

        return $this->attributes['submitted_by'] ?? null;
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
