<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'current_term',
        'status',
    ];

    /**
     * Get the classes associated with this academic year.
     */
    public function classes(): HasMany
    {
        return $this->hasMany(StudentClass::class, 'academic_year_id');
    }

    /**
     * Get the sessions associated with this academic year.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'academic_year_id');
    }
}
