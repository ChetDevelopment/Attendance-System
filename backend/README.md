# Backend

Laravel 10 backend for the Attendance System.

## Responsibilities

- Authentication and role-aware user flows
- Attendance, sessions, classes, academic years, and student management
- Admin, teacher, education, and student API endpoints
- Biometric scan tracking
- Reports, predictions, notifications, and activity logging
- Redis-backed caching helpers and optional Telegram integration

## Quick Start

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

If you work on backend assets too:

```powershell
npm install
npm run dev
```

## Important Paths

- [routes/api.php](/c:/Users/USER/Desktop/Attendance-System/backend/routes/api.php)
- [app/Http/Controllers](/c:/Users/USER/Desktop/Attendance-System/backend/app/Http/Controllers)
- [database/migrations](/c:/Users/USER/Desktop/Attendance-System/backend/database/migrations)
- [database/seeders](/c:/Users/USER/Desktop/Attendance-System/backend/database/seeders)

## Environment Highlights

- `DB_*`
- `REDIS_*`
- `QUEUE_CONNECTION`
- `TELEGRAM_*`
- `TIMETABLE_EVENTS_URL`
- `TEACHER_CALENDAR_ID`

See the project-level guide at [README.md](/c:/Users/USER/Desktop/Attendance-System/README.md) for the full setup.
