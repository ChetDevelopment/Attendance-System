<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'session_id',
        'status',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    const STATUS_PRESENT = 'Present';
    const STATUS_ABSENT = 'Absent';
    const STATUS_LATE = 'Late';
    const STATUS_EXCUSED = 'Excused';

    /**
     * Get the student associated with this record.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the session associated with this record.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
