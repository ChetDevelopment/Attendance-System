**BLACKBOXAI DEBUG & FIX Prompt:**

```
TASK: Find/Fix ALL Attendance-System errors/bugs

1. 🔍 AUDIT:
   - execute_command: cd backend && php artisan serve
   - execute_command: cd frontend && npm run dev  
   - Test login → ALL 3 dashboards → Report console/network/500 errors

2. 🐛 PRIORITY BUGS:
   - Login redirect fails? Check auth.js + backend /auth/login
   - Dashboard empty? Verify API responses match frontend expectations
   - Vue warnings? Missing props/types in components
   - Backend 500s? Controller exceptions (check AdminDashboardController.php)

3. 🛠️ FIX FLOW:
   - read_file failing endpoints/components
   - edit_file: Fix API paths, error handling, data mapping  
   - execute_command: Test fixes live
   - Update TODO.md progress

4. ✅ COMPLETE WHEN:
   - No console errors
   - All APIs 200 OK with data
   - Dashboards show LIVE stats/schedule/students
   - `npm run dev + php artisan serve` clean

Start: list_files frontend/src + search_files backend "error|exception|try"
```

