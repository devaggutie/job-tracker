# Job Tracker

Track job applications through your pipeline — add roles, move them through statuses, and see what needs a follow-up.

Portfolio project built with an AI-assisted workflow: the code, the Git habits, and the deployment are all part of the deliverable.

## Stack

| Layer     | Tech                                  |
| --------- | ------------------------------------- |
| Frontend  | React + Vite (JavaScript)             |
| Backend   | PHP 8.3 (no framework, PDO)           |
| Database  | MySQL 8                               |
| Local dev | Docker Compose                        |
| Hosting   | AWS (planned: S3 + CloudFront, EC2)   |

## Structure

```
api/        PHP backend (public/ docroot, src/ classes, migrations/)
web/        React frontend
docs/       AI workflow log, architecture notes
infra/      AWS deployment steps
```

## Quickstart

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec api composer install
docker compose exec api php bin/migrate.php
curl http://localhost:8080/health   # API health check
```

- API: http://localhost:8080
- MySQL: localhost:3306

## Tests

```bash
docker compose exec api ./vendor/bin/phpunit
```

## API

| Method | Path            | Description                        |
| ------ | --------------- | ---------------------------------- |
| GET    | /health         | health check                       |
| GET    | /auth/csrf      | get CSRF token for mutating calls  |
| POST   | /auth/register  | create account, returns session    |
| POST   | /auth/login     | start session, returns CSRF token  |
| POST   | /auth/logout    | destroy session                    |
| GET    | /auth/me        | current user                       |

All non-GET requests require the `X-CSRF-Token` header.

## Roadmap

- [x] M0 — Repo and workflow setup
- [x] M1 — Database and API skeleton
- [x] M2 — Auth
- [ ] M3 — Applications CRUD
- [ ] M4 — React UI
- [ ] M5 — Dashboard and follow-ups
- [ ] M6 — CI (GitHub Actions)
- [ ] M7 — AWS deployment
- [ ] M8 — Portfolio polish

## How this project uses AI

- [AGENTS.md](AGENTS.md) defines the conventions the AI assistant must follow.
- [docs/ai-workflow.md](docs/ai-workflow.md) is a running log of what was delegated, what was reviewed, and what the AI got wrong.
