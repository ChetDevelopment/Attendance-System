<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'class_id',
        'qr_code',
        'face_image',
        'is_active',
    ];

    // Relationship: Student belongs to a class
    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    // Relationship: Student has many attendance records
    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }
}