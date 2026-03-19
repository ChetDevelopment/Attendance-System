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
        'current_term',
        'status',
    ];

     public function classes()
    {
        return $this->hasMany(StudentClass::class);
    }
}
