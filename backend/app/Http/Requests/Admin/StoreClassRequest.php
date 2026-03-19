<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'class_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('classes', 'class_name')->where(fn ($query) => $query->where('academic_year_id', $this->academic_year_id)),
            ],
            'room_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        // Ensure academic_year_id is present
        if (!isset($data['academic_year_id'])) {
            $data['academic_year_id'] = $this->academic_year_id;
        }

        return $data;
    }
}
