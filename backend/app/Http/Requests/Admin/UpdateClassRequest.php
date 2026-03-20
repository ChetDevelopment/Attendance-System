<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $class = $this->route('class');
        $classId = is_object($class) ? $class->id : $class;

        return [
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'class_name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('classes', 'class_name')
                    ->ignore($classId)
                    ->where(fn ($query) => $query->where('academic_year_id', $this->academic_year_id)),
            ],
            'room_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        return parent::validated($key, $default);
    }
}
