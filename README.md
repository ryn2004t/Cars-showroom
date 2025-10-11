## Service Provider Web Dashboard (Prototype)

A demo prototype connecting service providers to customers. Includes:
- React + Vite frontend (TypeScript)
- PHP 8.0 backend with JWT auth and PDO MySQL
- MySQL 8.0 with phpMyAdmin
- Dockerized for quick evaluation

### One-command start
```bash
docker compose up -d --build
```

- Frontend: http://localhost:5173
- Backend API: http://localhost:8080/api
- phpMyAdmin: http://localhost:8081 (server: db, user: root, pass: root)

### Demo credentials
- Email: demo@example.com
- Password: DemoPass123!

### Initialize the database (first run)
The containers start immediately. Apply schema and seed data:
```bash
# Load schema
docker compose exec db sh -lc "mysql -uroot -proot service_provider < /app/database/schema.sql"

# Load seed data
docker compose exec db sh -lc "mysql -uroot -proot service_provider < /app/database/seed.sql"
```
If the files are not present inside the db container, copy them:
```bash
docker cp backend/database/schema.sql $(docker compose ps -q db):/app/database/schema.sql
docker cp backend/database/seed.sql $(docker compose ps -q db):/app/database/seed.sql
```

### Features included
- Secure login (JWT), role support (owner/manager/staff)
- Jobs, Customers, Media upload (with type/size checks)
- Invoices, Promotions, Reviews, Analytics summary, Settings
- CORS + security headers

### Tech notes
- PHP 8.0 (compatible with VS Code setups that avoid 8.3)
- Env config: backend/.env (see backend/.env.example)
- DB via PDO; manage with phpMyAdmin

### Local (without Docker)
- Backend: `cd backend && cp .env.example .env && composer install && php -S localhost:8080 -t public`
- Frontend: `cd frontend && cp .env.example .env && npm install && npm run dev`

### Screens/pages
- Dashboard, Jobs, Customers, Media, Invoices, Promotions, Reviews, Analytics, Settings

### Next steps (optional)
- Add CRUD forms for invoices/promotions
- Add role-based UI restrictions
- Add migrations/seeding automation
```