<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceRecord;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'late_after_minutes',
        'is_active',
        'order',
        'late_threshold',
        'description',
        'date',
        'academic_year_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'late_after_minutes' => 'integer',
        'late_threshold' => 'integer',
        'order' => 'integer',
        'date' => 'date',
    ];

    /**
     * Scope for active sessions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for sessions ordered by their sequence.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    
    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Check if a given time is within the session hours.
     */
    public function isTimeWithinSession($time = null): bool
    {
        $checkTime = $time instanceof Carbon ? $time : Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        $timeStr = $checkTime->format('H:i:s');
        
        return $timeStr >= $this->start_time && $timeStr <= $this->end_time;
    }

    /**
     * Check if a given time is considered late for this session.
     */
    public function isLate($time = null): bool
    {
        $checkTime = $time instanceof Carbon ? $time : Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        $threshold = $this->late_threshold ?? $this->late_after_minutes ?? 15;
        
        $startTime = Carbon::createFromFormat('H:i:s', $this->start_time, config('sessions.timezone', 'Asia/Bangkok'));
        $lateTime = $startTime->addMinutes($threshold);
        
        return $checkTime->greaterThan($lateTime);
    }

    /**
     * Calculate how many minutes late a given time is.
     */
    public function getMinutesLate($time = null): int
    {
        $checkTime = $time instanceof Carbon ? $time : Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        $startTime = Carbon::createFromFormat('H:i:s', $this->start_time, config('sessions.timezone', 'Asia/Bangkok'));
        
        if ($checkTime->lessThanOrEqualTo($startTime)) {
            return 0;
        }
        
        return (int) $checkTime->diffInMinutes($startTime);
    }

    /**
     * Helper to check if currently active.
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active && $this->isTimeWithinSession();
    }
}
