<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherActivity extends Model
{
    use HasFactory;

    protected $table = 'teacher_activities';

    protected $fillable = [
        'user_id',
        'student_id',
        'session_id',
        'action',
        'ip_address',
    ];

    /**
     * Get the user who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the student associated with the activity.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the session associated with the activity.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
