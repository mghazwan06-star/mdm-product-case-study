# Ideas, inefficiencies and known limitations

## Known limitations / shortcuts
| Limitation | Why we accepted it | What we'd do with more time |
|---|---|---|
| The backend runs on Laravel's built-in development server, and passwords in `docker-compose.yml` are local-only values | The brief asks for a local development setup only | A production web server (e.g. Nginx + PHP-FPM) and secrets kept outside the repo |
| Laravel's standard `users` table exists but isn't used (no login) | Kept Laravel's defaults; its `cache` tables will hold the "sync already running" lock | Add accounts and login, or remove the table |

## Improvement ideas
| Idea | Who benefits | Effort |
|---|---|---|

## Inefficiencies noticed
| Where | What | Fix idea |
|---|---|---|
