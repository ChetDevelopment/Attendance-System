# Vue 3 + Vite

This template should help get you started developing with Vue 3 in Vite. The template uses Vue 3 `<script setup>` SFCs, check out the [script setup docs](https://v3.vuejs.org/api/sfc-script-setup.html#sfc-script-setup) to learn more.

Learn more about IDE Support for Vue in the [Vue Docs Scaling up Guide](https://vuejs.org/guide/scaling-up/tooling.html#ide-support).


Student dashboard frontend export

This folder now contains the full student frontend section from the project.

Copied student files:
- `src/components/Student/AttendancePage.vue`
- `src/components/Student/AttendanceStudent.vue`
- `src/components/Student/BiometricScan.vue`
- `src/components/Student/DashboardPage.vue`
- `src/components/Student/DashboardStudent.vue`
- `src/components/Student/HistoryStudent.vue`
- `src/components/Student/SettingsStudent.vue`
- `src/components/Student/StudentLayout.vue`
- `src/pages/StudentDashboardPage.vue`

Copied support files:
- `src/components/types.ts`
- `src/services/api.js`
- `src/services/auth.js`
- `src/services/biometricService.js`
- `src/services/profileService.js`
- `src/services/storage.js`

What you likely need in your real project:
- Vue 3
- Vue Router
- Axios
- `lucide-vue-next`
- `jsqr`
- Tailwind CSS classes, or replace the utility classes with your own CSS

Student routes used in the original project:
- `/student/dashboard`
- `/student/attendance`
- `/student/biometric-scan`
- `/student/history`
- `/student/settings`

Expected API endpoints from these student screens:
- `GET /student/dashboard/stats`
- `GET /student/attendance/history`
- `POST /student/attendance/check-in`
- `POST /student/attendance/request`
- `POST /student/attendance/card-scan`
- `POST /student/attendance/fingerprint-scan`
- `POST /student/attendance/validate-biometric`
- `GET /student/attendance/biometric-history`
- `GET /student/attendance/biometric-status`
- `POST /student/attendance/student-info`
- `GET /user/profile`
- `POST /user/profile`
- `POST /user/settings`
- `POST /user/profile/avatar`

Environment variable used by `src/services/api.js`:
- `VITE_API_BASE_URL`

Notes:
- `StudentLayout.vue` depends on Vue Router because it uses `router-link` and `router-view`.
- `auth.js` and `storage.js` use `localStorage`.
- `DashboardPage.vue` contains merge conflict markers in the source project, so it was copied as-is.
