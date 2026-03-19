<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Role;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\AbsenceComment;
<<<<<<< HEAD
use App\Models\SchoolClass;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
=======
use App\Models\ActivityLog;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
>>>>>>> admin-dashboard-backend

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'student_id',
        'avatar_url',
        'is_active',
        'phone',
        'bio',
    ];

<<<<<<< HEAD
    /**
     * Attributes hidden for serialization
     *
     * @var string[]
     */
=======
>>>>>>> admin-dashboard-backend
    protected $hidden = [
        'password',
        'remember_token',
    ];
<<<<<<< HEAD
=======

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];
>>>>>>> admin-dashboard-backend

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

<<<<<<< HEAD
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
=======
    public function student()
    {
        return $this->belongsTo(Student::class);
>>>>>>> admin-dashboard-backend
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'submitted_by');
    }

    public function absenceComments()
    {
        return $this->hasMany(AbsenceComment::class, 'commented_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
<<<<<<< HEAD

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];
=======
>>>>>>> admin-dashboard-backend
}
