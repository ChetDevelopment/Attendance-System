<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    private const DEFAULT_STUDENT_PASSWORD = 'password123';
    private const INDEX_CACHE_VERSION_KEY = 'students:index:version';
    private const INDEX_CACHE_TTL_SECONDS = 60;

    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->input('per_page', 50), 200));
        $search = trim((string) $request->input('search', ''));
        $classId = $request->input('class_id');
        $academicYearId = $request->input('academic_year_id');
        $generation = trim((string) $request->input('generation', ''));
        $section = trim((string) $request->input('section', ''));

        $cacheVersion = (int) Cache::get(self::INDEX_CACHE_VERSION_KEY, 1);
        $cacheKey = 'students:index:v' . $cacheVersion . ':' . md5(json_encode([
            'page' => (int) $request->input('page', 1),
            'per_page' => $perPage,
            'search' => $search,
            'class_id' => $classId,
            'academic_year_id' => $academicYearId,
            'generation' => $generation,
            'section' => $section,
        ]));

        $payload = Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use (
            $perPage,
            $search,
            $classId,
            $academicYearId,
            $generation,
            $section
        ) {
            $query = Student::query()
                ->select([
                    'id',
                    'fullname',
                    'username',
                    'class',
                    'class_id',
                    'academic_year_id',
                    'parent_number',
                    'contact',
                    'gender',
                    'profile',
                    'generation',
                    'email',
                    'card_id',
                    'fingerprint_enrolled',
                    'last_biometric_scan',
                ])
                ->with('class:id,class_name')
                ->orderByDesc('id');

            if ($search !== '') {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('fullname', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($classId !== null && $classId !== '') {
                $query->where('class_id', (int) $classId);
            }

            if ($academicYearId !== null && $academicYearId !== '') {
                $query->where('academic_year_id', (int) $academicYearId);
            }

            if ($generation !== '') {
                $query->where('generation', $generation);
            }

            if ($section !== '') {
                $query->where(function ($builder) use ($section): void {
                    $builder->where('class', 'like', "%{$section}%")
                        ->orWhereHas('class', function ($classQuery) use ($section): void {
                            $classQuery->where('class_name', 'like', "%{$section}%");
                        });
                });
            }

            return $query->paginate($perPage)->toArray();
        });

        return response()->json($payload);
    }

    public function store(StoreStudentRequest $request)
    {
        $data = $request->validated();
        $plainPassword = (string) ($data['password'] ?? self::DEFAULT_STUDENT_PASSWORD);
        $data['password'] = $plainPassword;

        try {
            $roleId = Role::where('name', 'student')->value('id');
            if (!$roleId) {
                return response()->json([
                    'message' => 'Student role is not configured.',
                ], 500);
            }

            $student = null;
            $user = null;

            DB::transaction(function () use (&$student, &$user, $data, $plainPassword, $roleId): void {
                // Student model hashes password through mutator.
                $student = Student::create($data);

                // User model has 'password' => 'hashed' cast, so we pass plain password
                $user = User::create([
                    'name' => $data['fullname'],
                    'email' => $data['email'],
                    'password' => $plainPassword,
                    'role_id' => $roleId,
                    'student_id' => $student->id,
                    'avatar_url' => $data['profile'] ?? null,
                ]);
            });

            $this->bumpStudentIndexCacheVersion();
            Cache::forget('admin_users_list');

            return response()->json([
                'student' => $student,
                'user' => $user,
                'message' => 'Student and user account created successfully',
                'default_password' => self::DEFAULT_STUDENT_PASSWORD,
            ], 201);
        } catch (\Throwable $exception) {
            Log::error('Failed to create student.', [
                'email' => $data['email'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create student.',
            ], 500);
        }
    }

    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'default_password' => ['nullable', 'string', 'min:6', 'max:100'],
            'students' => ['required', 'array', 'min:1', 'max:300'],
            'students.*.fullname' => ['required', 'string', 'max:255'],
            'students.*.username' => ['required', 'string', 'max:255', 'distinct', 'unique:students,username'],
            'students.*.email' => ['required', 'email', 'max:255', 'distinct', 'unique:students,email', 'unique:users,email'],
            'students.*.generation' => ['required', 'string', 'max:100'],
            'students.*.class' => ['nullable', 'string', 'max:100'],
            'students.*.class_id' => ['nullable', 'exists:classes,id'],
            'students.*.academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'students.*.profile' => ['nullable', 'string', 'max:255'],
            'students.*.gender' => ['required', 'in:Male,Female'],
            'students.*.parent_number' => ['required', 'string', 'max:30'],
            'students.*.contact' => ['required', 'string', 'max:30'],
            'students.*.card_id' => ['nullable', 'string', 'max:255', 'distinct', 'unique:students,card_id'],
            'students.*.fingerprint_template' => ['nullable', 'string'],
            'students.*.fingerprint_enrolled' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $plainPassword = (string) ($validated['default_password'] ?? self::DEFAULT_STUDENT_PASSWORD);
        $rows = $validated['students'];

        $roleId = Role::where('name', 'student')->value('id');
        if (!$roleId) {
            return response()->json([
                'message' => 'Student role is not configured.',
            ], 500);
        }

        $createdStudentIds = [];

        try {
            DB::transaction(function () use ($rows, $plainPassword, $roleId, &$createdStudentIds): void {
                foreach ($rows as $row) {
                    $studentPayload = $row;
                    $studentPayload['password'] = $plainPassword;

                    $student = Student::create($studentPayload);
                    $createdStudentIds[] = $student->id;

                    User::create([
                        'name' => $row['fullname'],
                        'email' => $row['email'],
                        'password' => $plainPassword,
                        'role_id' => $roleId,
                        'student_id' => $student->id,
                        'avatar_url' => $row['profile'] ?? null,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Log::error('Failed to bulk create students.', [
                'count' => count($rows),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to bulk create students.',
            ], 500);
        }

        $this->bumpStudentIndexCacheVersion();
        Cache::forget('admin_users_list');

        return response()->json([
            'message' => 'Students created successfully.',
            'created_count' => count($createdStudentIds),
            'default_password' => self::DEFAULT_STUDENT_PASSWORD,
            'student_ids' => $createdStudentIds,
        ], 201);
    }

    public function show(Student $student)
    {
        return $student->load('class');
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $student->update($request->validated());
        $this->bumpStudentIndexCacheVersion();
        return response()->json($student);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        $this->bumpStudentIndexCacheVersion();
        return response()->json(['message' => 'Student deleted']);
    }

    public function uploadPhoto(Request $request, Student $student)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        $path = $validated['photo']->store('student-profiles', 'public');
        $url = Storage::url($path);

        $student->update([
            'profile' => $url,
        ]);

        $this->bumpStudentIndexCacheVersion();

        return response()->json([
            'message' => 'Student photo updated successfully',
            'profile' => $url,
        ]);
    }

    private function bumpStudentIndexCacheVersion(): void
    {
        $next = (int) Cache::get(self::INDEX_CACHE_VERSION_KEY, 1) + 1;
        Cache::forever(self::INDEX_CACHE_VERSION_KEY, $next);
    }
}
