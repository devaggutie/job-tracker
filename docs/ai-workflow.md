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
