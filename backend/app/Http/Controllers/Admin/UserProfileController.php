<?php

namespace App\Http\Controllers\Admin;

use App\Models\StudentClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    private const STUDENT_EMAIL_REGEX = '/^[a-z]+\.[a-z]+@student\.passerellesnumeriques\.org$/i';

    private function filterExistingUserColumns(array $data): array
    {
        $existing = [];

        foreach ($data as $column => $value) {
            if (Schema::hasColumn('users', $column)) {
                $existing[$column] = $value;
            }
        }

        return $existing;
    }

    private function missingUserColumns(array $columns): array
    {
        $missing = [];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('users', $column)) {
                $missing[] = $column;
            }
        }

        return $missing;
    }

    private function payload($user): array
    {
        $user->loadMissing(['role', 'student.class']);
        $hasTheme = Schema::hasColumn('users', 'theme');
        $hasLanguage = Schema::hasColumn('users', 'language');
        $hasNotificationEmail = Schema::hasColumn('users', 'notification_email');
        $hasNotificationPush = Schema::hasColumn('users', 'notification_push');
        $hasTwoFactorEnabled = Schema::hasColumn('users', 'two_factor_enabled');
        $hasFaceIdEnabled = Schema::hasColumn('users', 'face_id_enabled');
        $student = $user->student;
        $studentClass = null;

        if ($student?->class_id) {
            $studentClass = StudentClass::query()->find($student->class_id);
        }

        $roleName = strtolower((string) optional($user->role)->name);
        // Only use student name for users with student role
        $displayName = ($roleName === 'student' && $student?->fullname) ? $student->fullname : $user->name;

        return [
            'id' => $user->id,
            'name' => $displayName,
            'email' => $user->email,
            'role' => $roleName,
            'avatar_url' => $user->avatar_url,
            'phone' => $user->phone,
            'bio' => $user->bio,
            'theme' => $hasTheme ? ($user->theme ?? 'light') : 'light',
            'language' => $hasLanguage ? ($user->language ?? 'en') : 'en',
            'notification_email' => $hasNotificationEmail ? (bool) ($user->notification_email ?? true) : true,
            'notification_push' => $hasNotificationPush ? (bool) ($user->notification_push ?? true) : true,
            'two_factor_enabled' => $hasTwoFactorEnabled ? (bool) ($user->two_factor_enabled ?? false) : false,
            'face_id_enabled' => $hasFaceIdEnabled ? (bool) ($user->face_id_enabled ?? false) : false,
            'student' => $student ? [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'fullname' => $student->fullname,
                'username' => $student->username,
                'email' => $student->email,
                'class_name' => $studentClass?->class_name ?: (is_string($student->class ?? null) ? $student->class : null),
                'profile' => $student->profile,
            ] : null,
        ];
    }

    public function show(Request $request)
    {
        $user = $request->user();
        // Refresh to get latest data from database
        $user->refresh();
        return response()->json($this->payload($user));
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $user->loadMissing('role');
            $userRole = strtolower(optional($user->role)->name ?? '');

            // Build validation rules based on user role
            $emailRules = ['sometimes', 'email', 'max:255'];

            // Only students must use student.passerellesnumeriques.org email
            if ($userRole === 'student') {
                $emailRules[] = 'regex:' . self::STUDENT_EMAIL_REGEX;
            }

            // Email must be unique (excluding current user)
            $emailRules[] = 'unique:users,email,' . $user->id;

            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'email' => $emailRules,
                'avatar_url' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'bio' => ['nullable', 'string', 'max:2000'],
                'theme' => ['nullable', 'string', 'in:light,dark'],
            ], [
                'email.regex' => 'Email must use format firstname.lastname@student.passerellesnumeriques.org',
            ]);

            Log::debug('Updating profile for user ' . $user->id, $validated);

            $missingColumns = $this->missingUserColumns(array_keys($validated));
            if (!empty($missingColumns)) {
                Log::error('Cannot update profile because users table columns are missing.', [
                    'user_id' => $user->id,
                    'missing_columns' => $missingColumns,
                ]);

                return response()->json([
                    'message' => 'Profile fields are not ready in database. Please run migrations.',
                    'missing_columns' => $missingColumns,
                ], 500);
            }

            // Use the model's fill and save to ensure fillable and events are triggered
            $user->fill($validated);
            $saved = $user->save();

            if ($saved && $user->student && array_key_exists('name', $validated)) {
                $user->student->fullname = $validated['name'];
                $user->student->save();
            }

            if (!$saved) {
                Log::error('Failed to save user model for profile update', ['user_id' => $user->id]);
                return response()->json(['message' => 'Failed to save changes to database'], 500);
            }

            // Refresh to get latest data
            $user->refresh();

            Log::info('Profile updated successfully for user ' . $user->id);

            return response()->json($this->payload($user));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('Failed to update profile.', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update profile: ' . $exception->getMessage(),
            ], 500);
        }
    }

    public function updateSettings(Request $request)
    {
        try {
            $user = $request->user();
            $validated = $request->validate([
                'theme' => ['nullable', 'string', 'in:light,dark'],
                'language' => ['nullable', 'string', 'in:en,fr,km'],
                'notification_email' => ['nullable', 'boolean'],
                'notification_push' => ['nullable', 'boolean'],
                'two_factor_enabled' => ['nullable', 'boolean'],
                'face_id_enabled' => ['nullable', 'boolean'],
            ]);

            $persistableSettings = $this->filterExistingUserColumns($validated);
            $missingColumns = array_values(array_diff(array_keys($validated), array_keys($persistableSettings)));

            if (empty($persistableSettings)) {
                return response()->json([
                    'message' => 'Settings fields are not ready in database. Please run migrations.',
                    'missing_columns' => $missingColumns,
                ], 500);
            }

            if (!empty($missingColumns)) {
                Log::warning('Skipping unavailable settings columns while saving profile settings.', [
                    'user_id' => $user->id,
                    'missing_columns' => $missingColumns,
                ]);
            }

            $user->fill($persistableSettings);
            $user->save();

            // Refresh to get latest data
            $user->refresh();

            return response()->json([
                'message' => 'Settings saved successfully',
                'user' => $this->payload($user),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed to update settings.', [
                'user_id' => optional($request->user())->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update settings.',
            ], 500);
        }
    }

    public function uploadAvatar(Request $request)
    {
        try {
            $user = $request->user();
            $validated = $request->validate([
                'avatar' => ['required', 'image', 'max:2048'],
            ]);

            $missingColumns = $this->missingUserColumns(['avatar_url']);
            if (!empty($missingColumns)) {
                Log::error('Cannot upload avatar because users table columns are missing.', [
                    'user_id' => $user->id,
                    'missing_columns' => $missingColumns,
                ]);

                return response()->json([
                    'message' => 'Avatar field is not ready in database. Please run migrations.',
                    'missing_columns' => $missingColumns,
                ], 500);
            }

            $path = $validated['avatar']->store('avatars', 'public');
            $url = Storage::disk('public')->url($path);

            $user->update([
                'avatar_url' => $url,
            ]);

            // Refresh the user model to get updated values
            $user->refresh();

            return response()->json([
                'message' => 'Avatar updated successfully',
                'avatar_url' => $url,
                'user' => $this->payload($user),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed to upload avatar.', [
                'user_id' => optional($request->user())->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to upload avatar.',
            ], 500);
        }
    }
}
