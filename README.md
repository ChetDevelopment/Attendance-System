# Attendance System

A full-stack school attendance platform built with Laravel, Vue 3, MySQL, Redis, and Docker. The project supports role-based workflows for administrators, teachers, education staff, and students, with features such as attendance tracking, biometric check-in support, reporting, notifications, and operational dashboards.

## Overview

This repository is split into three main parts:

- `backend/`: Laravel 10 API, business logic, database migrations, seeders, jobs, and integrations
- `frontend/`: Vue 3 + Vite application for admin, teacher, education, and student interfaces
- `nginx/` and `docker-compose.yml`: container-based deployment and reverse proxy setup

## Core Features

- Role-based authentication for admin, teacher, education, training, and student users
- Attendance recording and attendance record correction workflows
- Student self-service attendance history and biometric scan screens
- Teacher dashboard with schedule, attendance submission, and notifications
- Admin dashboard with user, student, class, academic year, session, and biometric management
- Education dashboard for absence follow-up, risk monitoring, reporting, and alerts
- Attendance analytics, exports, and prediction endpoints
- Activity logging and optional Telegram notifications
- Redis-backed caching and real-time check-in helpers

## Tech Stack

- Backend: Laravel 10, PHP 8.1, Sanctum, MySQL, Redis
- Frontend: Vue 3, Vite, Vue Router, TypeScript, Tailwind CSS, Chart.js
- Infrastructure: Docker Compose, Nginx, MySQL 8, Redis 7

## Project Structure

```text
Attendance-System/
|-- backend/          Laravel API and database layer
|-- frontend/         Vue application
|-- nginx/            Nginx config for container deployment
|-- docker-compose.yml
|-- TODO.md
|-- BUG_FIX_PROMPT.md
```

## Main User Flows

### Admin

- View dashboard metrics and system-wide attendance data
- Manage users, students, classes, academic years, and sessions
- Review attendance records and apply manual corrections
- Manage biometric enrollment and student scan history
- Access reports, predictions, notifications, backups, and activity logs

### Teacher

- View assigned schedule and dashboard summaries
- Submit attendance for classes and review history
- View notifications and justification-related data

### Education Team

- Monitor absent and at-risk students
- Add comments, follow-ups, and absence status updates
- Export class summary reports and attendance data
- Send alerts for attendance follow-up workflows

### Student

- Sign in and view dashboard statistics
- View attendance history
- Submit attendance or manual requests
- Use card scan and biometric validation flows
- Update profile and settings

## Local Development Setup

### Prerequisites

- PHP 8.1+
- Composer
- Node.js 18+ and npm
- MySQL 8+
- Redis 7+ optional but recommended

### 1. Clone and install dependencies

```powershell
git clone <your-repo-url>
cd Attendance-System

cd backend
composer install
npm install

cd ..\frontend
npm install
```

### 2. Configure the backend environment

Copy the backend environment file and update database, Redis, and optional integration settings:

```powershell
cd backend
Copy-Item .env.example .env
php artisan key:generate
```

Important backend variables:

- `APP_URL`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
- `QUEUE_CONNECTION`
- `TELEGRAM_ENABLED`, `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`
- `TIMETABLE_EVENTS_URL`
- `TEACHER_CALENDAR_ID`

### 3. Configure the frontend environment

Create `frontend/.env` if you want to override the default API path:

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_APP_VERSION=local
```

Optional variables used by the frontend include:

- `VITE_API_BASE_URL`
- `VITE_APP_VERSION`
- `VITE_GOOGLE_API_KEY`

### 4. Prepare the database

```powershell
cd backend
php artisan migrate --seed
php artisan storage:link
```

The default database seeder loads roles, users, academic years, sessions, classes, students, and attendance sample data.

### 5. Start the app

Backend API:

```powershell
cd backend
php artisan serve
```

Backend asset watcher:

```powershell
cd backend
npm run dev
```

Frontend app:

```powershell
cd frontend
npm run dev
```

Typical local URLs:

- Frontend: `http://localhost:5173`
- Backend API: `http://localhost:8000`

## Docker Setup

The repository also includes a Docker Compose stack for MySQL, Redis, backend, frontend, and Nginx.

### Required root-level environment values

`docker-compose.yml` expects these values to be available from your shell or a root `.env` file:

- `DB_PASSWORD`
- `REDIS_PASSWORD`
- `JWT_SECRET`
- `DOCKER_USERNAME`

### Start the stack

```powershell
docker compose up -d
```

Services defined in the compose file:

- `db`: MySQL 8
- `redis`: Redis 7 Alpine
- `backend`: Laravel application container
- `frontend`: Vue frontend container
- `webserver`: Nginx reverse proxy

## Authentication Notes

- The active API uses Laravel Sanctum token creation in `backend/app/Http/Controllers/AuthController.php`
- The frontend stores the access token and user data in local storage
- Role-based redirects are handled in `frontend/src/router/index.js`
- Some legacy JWT-related code still exists in the backend, but the main application flow is centered around the current `/api/auth/*` endpoints

## Key API Areas

Examples of active API groups:

- `/api/auth/*`
- `/api/student/*`
- `/api/teacher/*`
- `/api/admin/*`
- `/api/education/*`
- `/api/collaboration/*`
- `/api/notifications/*`

See [backend/routes/api.php](/c:/Users/USER/Desktop/Attendance-System/backend/routes/api.php) for the main route definitions.

## Useful Commands

Backend:

```powershell
cd backend
php artisan migrate
php artisan db:seed
php artisan test
```

Frontend:

```powershell
cd frontend
npm run dev
npm run build
npm run lint
```

## Notes And Caveats

- The repository contains a mix of current production code and historical experiments or optimization notes
- `backend/README.md` and `frontend/README.md` were previously framework placeholders and are now reduced to module-level references
- Docker images for `backend` and `frontend` are configured to use `${DOCKER_USERNAME}/attendance-backend:latest` and `${DOCKER_USERNAME}/attendance-frontend:latest`
- Telegram integration is optional and should be configured only on the backend

## Additional Documentation

- [backend/README.md](/c:/Users/USER/Desktop/Attendance-System/backend/README.md)
- [frontend/README.md](/c:/Users/USER/Desktop/Attendance-System/frontend/README.md)
- [docker-compose.yml](/c:/Users/USER/Desktop/Attendance-System/docker-compose.yml)

