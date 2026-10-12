# Ideas, inefficiencies and known limitations

## Known limitations / shortcuts
| Limitation | Why we accepted it | What we'd do with more time |
|---|---|---|
| The backend runs on Laravel's built-in development server, and passwords in `docker-compose.yml` are local-only values | The brief asks for a local development setup only | A production web server (e.g. Nginx + PHP-FPM) and secrets kept outside the repo |
| For now, a laptop that disappears from Jamf stays as it was (same as the reference code) | "No longer in Jamf" (D2) is Tier 2; the engine already tracks every serial it saw | Build D2: mark laptops not seen in a successful sync, then remove this line |
| Times on the laptop page are shown in UTC (e.g. 21:10 when it's 23:10 in Amsterdam) | Simple and consistent for a first version | Show times in the viewer's own time zone |
| After a delete, the "… was deleted" note stays on screen next to a later sync message | Small; closes with one click | Clear the note when a new sync starts |
| The laptops and people lists come back whole (no paging) | Fine for 7 laptops and 6 people | Page the API lists and the screens |
| Pagination isn't implemented because the mock is one page; the count check is already written for the multi-page case | The brief provides a single-page mock file | Fetch every page from the real Jamf API, then run the same count check |
| Laravel's standard `users` table exists but isn't used (no login) | Kept Laravel's defaults; its `cache` tables will hold the "sync already running" lock | Add accounts and login, or remove the table |

## Improvement ideas
| Idea | Who benefits | Effort |
|---|---|---|

## Inefficiencies noticed
| Where | What | Fix idea |
|---|---|---|
