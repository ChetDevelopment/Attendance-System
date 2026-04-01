<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Session;

class StudentClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'code',
        'description',
        'academic_year_id',
    ];

    // Accessor for class_name alias (frontend compatibility)
    public function getClassNameAttribute(): string
    {
        // Prefer class_name column if it exists, otherwise fall back to name
        if (isset($this->attributes['class_name']) && $this->attributes['class_name']) {
            return $this->attributes['class_name'];
        }
        return $this->attributes['name'] ?? '';
    }

    // Accessor for name alias
    public function getNameAttribute(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Get the academic year associated with this class.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Get sessions for this class through academic year.
     */
    public function sessions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Session::class,
            AcademicYear::class,
            'id',
            'academic_year_id'
        );
    }
}
