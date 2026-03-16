<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SchoolClass;
use App\Models\AttendanceRecord;

class Student extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_code',
        'first_name',
        'last_name',
        'email',
        'gender',
        'date_of_birth',
        'class_id',
        'qr_code',
        'card_id',
        'face_image',
        'parent_number',
        'contact',
        'is_active',
    ];

    /**
     * Get the class that owns the student.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
