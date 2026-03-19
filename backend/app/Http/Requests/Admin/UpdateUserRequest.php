<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    private const STUDENT_EMAIL_REGEX = '/^[a-z]+\.[a-z]+@student\.passerellesnumeriques\.org$/i';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = is_object($user) ? $user->id : $user;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                'regex:' . self::STUDENT_EMAIL_REGEX,
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['sometimes', 'exists:roles,id'],
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
