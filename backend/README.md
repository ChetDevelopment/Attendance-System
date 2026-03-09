# Attendance Backend (Laravel)

## Requirements
- PHP `8.2` or newer
- Composer `2.x`
- MySQL or MariaDB

## Why backend did not start
This project currently installs dependencies that require PHP `>= 8.2`.
If `php -v` shows `8.0.x` or `8.1.x`, `php artisan serve` will fail before boot.

## Setup
1. Install/upgrade PHP to `8.2+`.
2. Confirm terminal PHP version:
   ```powershell
   php -v
   ```
3. Install dependencies:
   ```powershell
   composer install
   ```
4. Create environment file and app key:
   ```powershell
   copy .env.example .env
   php artisan key:generate
   ```
5. Configure database values in `.env`, then run:
   ```powershell
   php artisan migrate --seed
   ```
6. Start backend:
   ```powershell
   php artisan serve
   ```

## Windows PATH note
If you upgraded PHP but still see old version, update `PATH` so the PHP `8.2+` binary comes before older PHP folders, then open a new terminal.
