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
        'gender',
        'date_of_birth',
        'class_id',
        'qr_code',
        'face_image',
        'is_active',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
