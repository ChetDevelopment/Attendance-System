<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $student = $this->route('student');
        $studentId = is_object($student) ? $student->id : $student;

        return [
            'fullname' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('students', 'username')->ignore($studentId)],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('students', 'email')->ignore($studentId)],
            'generation' => ['sometimes', 'string', 'max:100'],
            'class' => ['nullable', 'string', 'max:100'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'profile' => ['nullable', 'string', 'max:255'],
            'gender' => ['sometimes', 'in:Male,Female'],
            'parent_number' => ['sometimes', 'string', 'max:30'],
            'contact' => ['sometimes', 'string', 'max:30'],
            // Biometric fields
            'card_id' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('students', 'card_id')->ignore($studentId)],
            'fingerprint_template' => ['nullable', 'string'],
            'fingerprint_enrolled' => ['nullable', 'boolean'],
        ];
    }
}
