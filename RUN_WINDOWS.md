### Run Frontend + Backend Together (Windows)

This setup serves the frontend build from the backend's `public/` folder and routes API under `/api`.

1) Backend env and deps
- Install PHP 8.0 (on PATH)
- Install Composer
- In `backend`: copy env and install deps
```
copy .env.example .env
composer install
```
- Configure DB in `.env` and create DB using phpMyAdmin or MySQL
- Import schema and seed (from PowerShell):
```
mysql -uroot -p service_provider < backend\database\schema.sql
mysql -uroot -p service_provider < backend\database\seed.sql
```

2) Build frontend into backend/public
- From `frontend`:
```
$env:VITE_API_BASE_URL="/api"
npm install
npm run build
```
This outputs assets into `backend/public/`.

3) Run a single web server (PHP built-in) from backend/public
```
cd backend\public
php -S localhost:8080 router.php
```
Open http://localhost:8080

Notes
- During development, you can still run `npm run dev` in `frontend` and have it proxy to `http://localhost:8080`.
- If you change frontend code, re-run `npm run build` to refresh assets in `backend/public`.
