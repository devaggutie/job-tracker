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

## 2026-11-08 — M0: project scaffold and environment setup

- Model: mimo-v2.6-flash-free (opencode)
- Delegated: project research (stack fit, AWS free-tier options, portfolio framing), roadmap and milestone design, all M0 files (README, AGENTS.md, docker-compose.yml, Apache config, .gitignore, this log).
- Reviewed: Git remote fix — assistant corrected a malformed `git remote add origin` command; verified `git ls-remote` exit code 0 confirmed SSH auth as `devaggutie`. Docker stack verified by running health check before first commit.
- Got wrong: initial `git remote add` guidance produced an error (stray argument in the command); opencode install reported a version mismatch (1.19.5 vs 1.18.33) that needed a manual `opencode upgrade`. Free-model API key error had to be resolved by picking a `-free` model via `/models` before any generation worked.
- Outcome: merged as initial scaffold commits on `main`.
