<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:academic_years,name'],
            'current_term' => ['required', 'in:Term1,Term2,Term3,Term4'],
            'status' => ['required', 'in:Current,Close'],
        ];
    }
}
