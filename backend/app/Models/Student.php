<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Classes;

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

    public function class()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
