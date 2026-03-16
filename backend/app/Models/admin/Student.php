<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'fullname',
        'username',
        'email',
        'password',
        'generation',
        'class',
        'class_id',
        'academic_year_id',
        'profile',
        'gender',
        'parent_number',
        'contact',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Get the class associated with this student.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }

    /**
     * Get the academic year associated with this student.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Get the user account associated with this student.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'email', 'email');
    }

    /**
     * Hash password when setting it.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? Hash::make($value) : null;
    }
}
