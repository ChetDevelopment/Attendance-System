<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicYear = $this->route('academic_year');
        $academicYearId = is_object($academicYear) ? $academicYear->id : $academicYear;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('academic_years', 'name')->ignore($academicYearId)],
            'current_term' => ['sometimes', 'in:Term1,Term2,Term3,Term4'],
            'status' => ['sometimes', 'in:Current,Close'],
        ];
    }
}
