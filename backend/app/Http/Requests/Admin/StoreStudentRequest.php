<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    // More permissive regex for student emails - allows alphanumeric and dots
    private const STUDENT_EMAIL_REGEX = '/^[a-z0-9][a-z0-9.]*[a-z0-9]@student\.passerellesnumeriques\.org$/i';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullname' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:students,username'],
            'email' => [
                'required',
                'email',
                'max:255',
                'regex:' . self::STUDENT_EMAIL_REGEX,
                'unique:students,email',
                'unique:users,email',
            ],
            'generation' => ['required', 'string', 'max:100'],
            'class' => ['nullable', 'string', 'max:100'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'profile' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'in:Male,Female'],
            'parent_number' => ['required', 'string', 'max:30'],
            'contact' => ['required', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            // Biometric fields
            'card_id' => ['nullable', 'string', 'max:255', 'unique:students,card_id'],
            'fingerprint_template' => ['nullable', 'string'],
            'fingerprint_enrolled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.regex' => 'Email must use format name@student.passerellesnumeriques.org (only letters, numbers, and dots allowed)',
        ];
    }
}
