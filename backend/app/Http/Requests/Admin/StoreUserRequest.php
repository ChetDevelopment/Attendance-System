<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    private const STUDENT_EMAIL_REGEX = '/^[a-z]+\.[a-z]+@student\.passerellesnumeriques\.org$/i';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'student_id' => ['nullable', 'exists:students,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.regex' => 'Email must use format firstname.lastname@student.passerellesnumeriques.org',
        ];
    }
}
