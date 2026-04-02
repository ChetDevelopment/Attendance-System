# Frontend

Vue 3 + Vite frontend for the Attendance System.

## Responsibilities

- Login flow and role-based routing
- Admin dashboard and operations workspace
- Teacher dashboard and attendance workflow
- Education dashboard and follow-up views
- Student self-service dashboard, attendance history, settings, and biometric scan flow

## Quick Start

```powershell
npm install
npm run dev
```

Build for production:

```powershell
npm run build
```

Type-check:

```powershell
npm run lint
```

## Environment

The frontend reads API and build metadata from Vite env variables.

Example `frontend/.env`:

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_APP_VERSION=local
```

Optional variables:

- `VITE_API_BASE_URL`
- `VITE_APP_VERSION`
- `VITE_GOOGLE_API_KEY`

## Important Paths

- [src/router/index.js](/c:/Users/USER/Desktop/Attendance-System/frontend/src/router/index.js)
- [src/layouts/AppLayout.vue](/c:/Users/USER/Desktop/Attendance-System/frontend/src/layouts/AppLayout.vue)
- [src/pages](/c:/Users/USER/Desktop/Attendance-System/frontend/src/pages)
- [src/components](/c:/Users/USER/Desktop/Attendance-System/frontend/src/components)
- [src/services](/c:/Users/USER/Desktop/Attendance-System/frontend/src/services)

See the full project guide at [README.md](/c:/Users/USER/Desktop/Attendance-System/README.md).
