# Ideas, inefficiencies and known limitations

## Known limitations / shortcuts
| Limitation | Why we accepted it | What we'd do with more time |
|---|---|---|
| The backend runs on Laravel's built-in development server, and passwords in `docker-compose.yml` are local-only values | The brief asks for a local development setup only | A production web server (e.g. Nginx + PHP-FPM) and secrets kept outside the repo |
| For now, a laptop that disappears from Jamf stays as it was (same as the reference code) | "No longer in Jamf" (`DECISIONS.md` row 2) is planned for Tier 2; the engine already tracks every serial it saw | Mark laptops not seen in a successful sync, then remove this line |
| The sync looks up each laptop and person one at a time (one database query per laptop) | Fast enough for 7 laptops | Look up all existing laptops and people in one go before saving |
| The laptops and people lists come back whole (no paging) | Fine for 7 laptops and 6 people | Page the API lists and the screens |
| Pagination isn't implemented because the mock is one page; the count check is already written for the multi-page case | The brief provides a single-page mock file | Fetch every page from the real Jamf API, then run the same count check |
| Laravel's standard `users` table exists but isn't used (no login) | Kept Laravel's defaults; its `cache` tables will hold the "sync already running" lock | Add accounts and login, or remove the table |

## Improvement ideas
| Idea | Who benefits | Effort |
|---|---|---|

## Inefficiencies noticed
| Where | What | Fix idea |
|---|---|---|
