<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Role;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\AbsenceComment;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id'
    ];

    protected $hidden = ['password'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
<<<<<<< HEAD

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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    
    
}
=======
}
>>>>>>> feature/admin_management_api
