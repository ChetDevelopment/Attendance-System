<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolClass;

class AcademicYear extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }
}
