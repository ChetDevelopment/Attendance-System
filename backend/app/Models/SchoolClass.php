<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AcademicYear;
use App\Models\Student;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'code',
        'academic_year_id',
        'description',
        'is_active',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Class has many students
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    // Class belongs to a teacher
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
