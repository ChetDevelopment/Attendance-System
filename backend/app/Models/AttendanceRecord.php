<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class AttendanceRecord extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id',
        'session_id',
        'recorded_by',
        'attendance_date',
        'status',
        'check_in_time',
    ];

     // Attendance belongs to Student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Attendance belongs to Session
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    // Attendance recorded by User (Teacher)
    public function teacher()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

}
