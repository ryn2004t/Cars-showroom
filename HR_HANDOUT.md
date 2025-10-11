# Service Provider Web Dashboard — HR Handout

## What this prototype shows
- Provider login and protected dashboard
- Jobs, Customers, Media uploads
- Invoices, Promotions, Reviews
- Analytics summary and editable Settings

## How to run (Docker)
1) Install Docker + Docker Compose
2) In the project root, run:
```bash
docker compose up -d --build
```
3) Initialize database (first run only):
```bash
docker compose exec db sh -lc "mysql -uroot -proot service_provider < /app/database/schema.sql"
docker compose exec db sh -lc "mysql -uroot -proot service_provider < /app/database/seed.sql"
```
4) Open the prototype:
- Frontend: http://localhost:5173
- Backend API: http://localhost:8080/api
- phpMyAdmin: http://localhost:8081 (server: db, user: root, pass: root)

Demo login: demo@example.com / DemoPass123!

## Pages to click through
- Dashboard (upcoming jobs summary)
- Jobs (placeholder)
- Customers (placeholder list)
- Media (upload UI comes later; endpoint is ready)
- Invoices (list)
- Promotions (list)
- Reviews (list)
- Analytics (KPIs)
- Settings (key-value editor)

## Notes
- Tech stack: React + Vite (TypeScript), PHP 8.0 (JWT), MySQL 8.0
- Security: CORS and headers enabled, JWT auth, validated inputs
- DB managed with phpMyAdmin; schema and seed included

## Contact
For engineering follow-ups, please share this repo link and handout with the dev team.
