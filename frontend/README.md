# Frontend (Vue 3 + Vite)

Frontend app for the Attendance System.

## Tech Stack

- Vue 3
- Vite
- Vue Router
- Axios
- Tailwind CSS (via `@tailwindcss/postcss`)

## Requirements

- Node.js 18+ (Node 20 LTS recommended)
- npm 9+

## Setup

1. Open terminal in `frontend`:
   ```bash
   cd frontend
   ```
2. Install dependencies:
   ```bash
   npm install
   ```

## Environment Variables

Create a `.env` file in `frontend/` (optional):

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

If not set, the app uses `http://127.0.0.1:8000/api` by default.

## Run Development Server

```bash
npm run dev
```

Default Vite URL is usually:

- `http://localhost:5173`

## Build for Production

```bash
npm run build
```

Preview production build locally:

```bash
npm run preview
```

## Available Scripts

- `npm run dev` - start Vite dev server
- `npm run build` - production build
- `npm run preview` - preview built app

## Project Structure

```text
frontend/
  src/
    components/
    layouts/
    pages/
    router/
    services/
    App.vue
    main.js
    style.css
  public/
  index.html
  vite.config.js
  postcss.config.js
```

## Backend Connection

This frontend expects the Laravel backend API to be running (default: `http://127.0.0.1:8000`).

Typical order:

1. Start backend API.
2. Start frontend (`npm run dev`).
3. Open frontend URL in browser.

## Troubleshooting

- If Vite fails on startup, verify `postcss.config.js` contains only PostCSS config (no app/runtime code).
- If API calls fail, confirm backend is running and `VITE_API_BASE_URL` is correct.
- If dependencies are inconsistent, remove `node_modules` and reinstall:
  ```bash
  rm -rf node_modules package-lock.json
  npm install
  ```
