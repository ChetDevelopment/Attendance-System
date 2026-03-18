<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;
use App\Models\StudentClass;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\User;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_code',
        'first_name',
        'last_name',
        'fullname',
        'username',
        'email',
        'password',
        'gender',
        'date_of_birth',
        'generation',
        'class',
        'class_id',
        'academic_year_id',
        'qr_code',
        'card_id',
        'face_image',
        'profile',
        'parent_number',
        'contact',
        'is_active',
        'fingerprint_template',
        'fingerprint_enrolled',
        'last_biometric_scan',
    ];

    protected $casts = [
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
        'fingerprint_enrolled' => 'boolean',
        'last_biometric_scan' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if ($student->fullname && (!$student->first_name || !$student->last_name)) {
                $parts = explode(' ', (string) $student->fullname, 2);
                $student->first_name = $student->first_name ?: ($parts[0] ?? '');
                $student->last_name = $student->last_name ?: ($parts[1] ?? '');
            }
            
            // Generate a student_code if missing
            if (!$student->student_code) {
                $student->student_code = $student->username ?: 'STU-' . uniqid();
            }
        });
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'student_id', 'id');
    }

    /**
     * Hash password when setting it.
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
        }
    }
}
