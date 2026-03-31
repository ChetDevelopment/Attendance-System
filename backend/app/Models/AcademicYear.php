<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StudentClass;

class AcademicYear extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'start_year',
        'end_year',
        'current_term',
        'status',
        'is_active',
    ];

     public function classes()
    {
        return $this->hasMany(StudentClass::class);
    }
}
