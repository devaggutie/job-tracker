# AGENTS.md

Instructions for AI assistants (and human contributors) working in this repository.

## Project

Job application tracker: users log applications, move them through statuses, and see a pipeline dashboard. Portfolio project — clean Git history and a documented AI workflow are part of the deliverable.

## Stack

- **Frontend:** React 18 + Vite, plain JavaScript (JSX). No TypeScript.
- **Backend:** PHP 8.3, no framework. PDO with prepared statements. JSON REST API.
- **Database:** MySQL 8. Migrations are plain numbered SQL files in `api/migrations/` (`001_create_users.sql`, `002_...`).
- **Local dev:** Docker Compose runs MySQL + PHP/Apache. React runs natively (`npm run dev` in `web/`).

## Layout

```
api/public/      Apache docroot — front controller (index.php)
api/src/         PHP classes (DB, auth, handlers)
api/migrations/  Numbered .sql migration files
web/             React app (Vite)
docs/            ai-workflow.md, architecture notes
infra/           AWS deployment steps (README.md)
```

## Conventions

- PHP: `declare(strict_types=1)` in every file; classes in `api/src/`; JSON responses shaped as `{"data": ...}` or `{"error": ...}` with correct HTTP status codes.
- SQL: prepared statements only. Never interpolate user input into SQL.
- React: function components; one component per file; data fetching through a small `api/` helper module, no inline `fetch` in components.
- Commits: Conventional Commits (`feat:`, `fix:`, `chore:`, `docs:`, `test:`), one logical change per commit.
- Branches: `feat/<slug>`, `fix/<slug>` — one issue, one branch, one PR.

## Hard rules

1. Never commit secrets. `.env` is gitignored; only `.env.example` with dummy values.
2. Every new API endpoint ships with a test.
3. No new dependencies without stating why in the PR description.
4. Before merging: run the test suite and `docker compose down` must not leave broken state.
5. Log significant AI-assisted work in `docs/ai-workflow.md` — including mistakes caught during review.

## Commands

```bash
docker compose up -d --build     # start MySQL + API
docker compose down              # stop (keeps data volume)
docker compose down -v           # stop and wipe database
docker compose exec api composer install   # install PHP dependencies
docker compose exec api php bin/migrate.php  # run pending migrations
docker compose exec api ./vendor/bin/phpunit # run tests
curl http://localhost:8080/health  # API health check
npm run dev                      # React dev server (in web/)
```
