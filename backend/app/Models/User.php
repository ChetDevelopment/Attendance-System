<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
use App\Models\SchoolClass;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id'
    ];

    /**
     * Attributes hidden for serialization
     *
     * @var string[]
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'recorded_by');
    }

    public function absenceComments()
    {
        return $this->hasMany(AbsenceComment::class, 'commented_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];
}
