# AI Workflow Log

Running log of how AI assistance is used in this project. Newest entries at the top.

**Template for each entry:**

```
## YYYY-MM-DD — <task>
- Model: <model name>
- Delegated: <what the AI was asked to do>
- Reviewed: <what you checked, changed, or rejected>
- Got wrong: <mistakes or gaps found> (write "none" if clean)
- Outcome: <merged / rewrote / discarded>
```

---

## 2026-11-24 — M6: CI (GitHub Actions)

- Model: mimo-v2.6-flash-free (opencode)
- Delegated: `.github/workflows/ci.yml` (two parallel jobs: backend `php -l` lint + PHPUnit against a MySQL 8 service container with the API served by `php -S` as a front controller, frontend `npm ci` + `npm run build` with Node 20 caching), `TEST_BASE_URL` support in `ApiTestCase`, and refactoring `HealthTest` onto the shared request helper.
- Reviewed: rehearsed the entire CI recipe in a one-off `job-tracker-api` container on the host network before pushing (same steps, same env, different port), re-ran the default Docker path with no `TEST_BASE_URL` set to confirm no regression, ran the exact `php -l` find/xargs pipeline, `npm ci` + build from a clean `node_modules`, and validated the workflow YAML with PyYAML.
- Got wrong: (1) `HealthTest` had its own private `request()` that hardcoded `http://127.0.0.1` — the rehearsal failed with connection-refused on all three of its tests, which is exactly why the recipe was rehearsed before pushing; fixed by extending `ApiTestCase` and deleting the duplicated helper; (2) first draft referenced job-level `env` inside `services.mysql.env` via `${{ env.* }}` — that context is not reliably available there, so service credentials are hardcoded explicitly.
- Outcome: rehearsed recipe 24/24 green, default path 24/24 green, lint clean, build clean, pushed as 3 conventional commits awaiting PR review. Note: M5 was still unmerged when this branch was cut, so CI will report 24 tests until M5 lands and this branch rebases.
## 2026-11-23 — M5: dashboard and follow-ups

- Model: mimo-v2.6-flash-free (opencode)
- Delegated: `GET /dashboard` endpoint (status counts, 8-week ISO series, follow-up queue), repository with `GROUP BY` / `YEARWEEK(..., 3)`, dashboard page with count cards, pure-CSS bar chart, and overdue highlighting, plus 4 tests.
- Reviewed: suite run, live curl of `/dashboard` (verified week Mondays and zero-fill), `npm run build`; read the week-key construction (`o` + padded `W`) against MySQL's `YEARWEEK` mode 3 by hand.
- Got wrong: (1) `Dashboard.php` initially omitted `use DateTimeImmutable` — would have fataled in namespace `App`; caught while re-reading the file before running, fixed pre-emptively; (2) the aggregation test created **three** applications with today's date but asserted a weekly sum of 2 — failed on first run, corrected to 3; (3) first draft of the weekStart assertion was an unreadable conditional expression, replaced with an explicit expected-Monday computation. Also note: the Docker containers were found stopped mid-session (`SIGWINCH` at 23:30) — not a code issue, restarted with `docker compose up -d`, but a reminder that a dead stack can masquerade as an app bug (same lesson as M4's `ECONNREFUSED`).
- Outcome: 28/28 tests (245 assertions), dashboard shape verified live, build clean, pushed as 3 conventional commits awaiting PR review.

## 2026-11-11 — M4: React UI

- Model: mimo-v2.6-flash-free (opencode)
- Delegated: full frontend scaffold (Vite + React 18), fetch wrapper with automatic CSRF handling, auth context + protected routing, five pages (login/register, list with filters, Kanban board, shared add/edit form, detail with timeline), and the stylesheet.
- Reviewed: `npm run build` (clean), dev-server proxy curls for `/health` and `/auth/csrf`, backend suite re-run to confirm no API drift; read `api.js` and the form payload builder carefully for the empty-string-vs-null salary/url cases.
- Got wrong: (1) `BoardPage` first draft used a dynamic `await import('../api')` for no reason — replaced with a normal static import before committing; (2) initial proxy verification failed with `ECONNREFUSED` because the Docker stack had been shut down after M3 — reminder to check `docker compose up` before blaming the proxy config; (3) form submission needed explicit `'' → null` conversion for url/salary/date fields, since the API rejects empty strings for numeric/date columns — caught while writing the payload builder, not by a failing test (the frontend has no test runner yet — a gap for a later milestone).
- Outcome: build clean, proxy verified, 24/24 API tests green, pushed as 3 conventional commits awaiting PR review. Note: react-router-dom added as the first new dependency — justified in the PR description per AGENTS.md.

## 2026-11-11 — M3: applications CRUD

- Model: mimo-v2.6-flash-free (opencode)
- Delegated: router path-param support (`/applications/{id}`), applications repository, CRUD handler with field validation, shared `Request`/`ApiTestCase` extraction (refactor), 12 new tests.
- Reviewed: full suite + manual curl run of create → status update → history timeline; read the dynamic-SQL builder in `Applications::update` carefully to confirm column names come only from an allowlist, never from request keys directly.
- Got wrong: (1) `testShowOtherUsersApplicationReturns404` destructured `actingAs()` as `[, , $cookieB]` — the helper returns two values, so `$cookieB` was null and the test hit 401 instead of 404; caught on the first suite run, one-line fix; (2) my manual curl chain silently produced no output because the register call omitted `-c`, so the regenerated session cookie was dropped and every later request 403'd — a tooling mistake, not code, but it's the kind of thing that looks like a real bug until you run the steps individually; (3) an earlier Router draft had a redundant ternary (`$params === [] ? $handler([]) : $handler($params)`) that always took the same branch — simplified before commit.
- Outcome: 24/24 tests green (195 assertions), manual flow shows `status_history` rows `null → applied → offer`, pushed as 4 conventional commits awaiting PR review.

## 2026-11-09 — M2: auth (sessions, CSRF, rate limiting)

- Model: mimo-v2.6-flash-free (opencode)
- Delegated: full auth implementation — session helpers, CSRF token validation, DB-backed login rate limiter, users repository, 5 endpoints, router CSRF enforcement, 9 new tests.
- Reviewed: ran suite and a manual curl flow (csrf → register → me → rejected logout); inspected cookie/session handling closely.
- Got wrong: (1) first draft of the flow test reused the pre-register session cookie after `session_regenerate_id(true)` — the old session is destroyed on regenerate, so requests would have 403'd; caught by reasoning through the test before running it, fixed to use the cookie returned by each mutating response; (2) duplicate-email detection initially keyed off `PDOException::getCode()`, which is not reliably the SQLSTATE — switched to `$e->errorInfo[0] === '23000'`. Also documented the tradeoff that failed logins are recorded per (ip, email) with a fixed 15-minute window.
- Outcome: 12/12 tests green (3 health + 9 auth), pushed as 4 conventional commits awaiting PR review.

## 2026-11-09 — M1: database and API skeleton

- Model: mimo-v2.6-flash-free (opencode)
- Delegated: migrations (4 tables), PDO layer, router, health endpoint, migration runner, PHPUnit setup — implemented end-to-end on `feat/m1-db-api-skeleton`.
- Reviewed: ran the stack and the test suite personally; inspected each file against AGENTS.md conventions before committing.
- Got wrong: (1) assumed the stock `php:8.3-apache` image ships `pdo_mysql` — it does not, caught by `php -m` during M0 verification; (2) Dockerfile initially lacked `zip`/`unzip`, so `composer install` failed; (3) first 404 test used `file_get_contents` without `ignore_errors`, which returns `false` on non-2xx — rewrote tests around a shared request helper. Initial Router draft also had an awkward `result['key']` indirection, simplified to an `error`-vs-`data` check before committing.
- Outcome: 3/3 tests passing, migrations idempotent (re-run skips applied files), pushed as 4 conventional commits awaiting PR review.

## 2026-11-08 — M0: project scaffold and environment setup

- Model: mimo-v2.6-flash-free (opencode)
- Delegated: project research (stack fit, AWS free-tier options, portfolio framing), roadmap and milestone design, all M0 files (README, AGENTS.md, docker-compose.yml, Apache config, .gitignore, this log).
- Reviewed: Git remote fix — assistant corrected a malformed `git remote add origin` command; verified `git ls-remote` exit code 0 confirmed SSH auth as `devaggutie`. Docker stack verified by running health check before first commit.
- Got wrong: initial `git remote add` guidance produced an error (stray argument in the command); opencode install reported a version mismatch (1.19.5 vs 1.18.33) that needed a manual `opencode upgrade`. Free-model API key error had to be resolved by picking a `-free` model via `/models` before any generation worked.
- Outcome: merged as initial scaffold commits on `main`.
