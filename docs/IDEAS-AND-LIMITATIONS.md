# Ideas, inefficiencies and known limitations

## Known limitations / shortcuts
| Limitation | Why we accepted it | What we'd do with more time |
|---|---|---|
| The backend runs on Laravel's built-in development server, and passwords in `docker-compose.yml` are local-only values | The brief asks for a local development setup only | A production web server (e.g. Nginx + PHP-FPM) and secrets kept outside the repo |
| If Jamf ever sends an empty list by mistake, every laptop is flagged "No longer in Jamf" until the next good sync | Nothing is deleted and the next good sync clears the flags; refusing short lists would also block real retirements (`DECISIONS.md` row 9) | Ask before applying a sync that would flag most laptops ("This sync would flag 5 of 7 laptops. Apply anyway?") |
| The sync looks up each laptop and person one at a time | Measured in testing 5.3: about 3 database queries per laptop; 5,000 laptops sync in about 3 seconds, 12,000 in about 7. Fast enough for a mid-size customer | Look up all existing laptops and people in one go before saving |
| The laptops and people lists come back whole (no paging, no search) | Measured in testing 5.3: 5,000 laptops appear in about a second, but the page is about 380 screens long, so finding one laptop means scrolling | Page the API lists and the screens; add search and filters |
| Pagination isn't implemented because the mock is one page; the count check is already written for the multi-page case | The brief provides a single-page mock file | Fetch every page from the real Jamf API, then run the same count check |
| Laravel's standard `users` table exists but isn't used (no login) | Kept Laravel's defaults; its `cache` tables will hold the "sync already running" lock | Add accounts and login, or remove the table |
| The sync reads the whole Jamf file into memory, so it fails above about 12,000 laptops (testing 5.3, finding F15) | Far above a mid-size customer; a live Jamf connection reads page by page anyway | Process the list page by page |
| Impossible values (battery 140%, negative memory) are shown as "Not provided by Jamf" (finding F8) | Rare; the label is only mildly untrue | Say "Jamf sent a value that isn't possible" |
| After a very large change, the sync message lists every laptop that needs attention (6,871 lines in testing 5.4, finding F14) | Only happens at large scale; the message is still true | Show the first 10, then "and 6,861 more" linking to the Needs attention filter |

## Improvement ideas
| Idea | Who benefits | Effort |
|---|---|---|
| Thousands separators: "4,897 unchanged" instead of "4897" (testing 5.3) | Larger customers, who read these numbers every day | Small |
| An alert when a sync stays "running" or fails (testing 5.3, 5.4) | IT teams, who would otherwise only notice on the next visit | Medium |

## Inefficiencies noticed
| Where | What | Fix idea |
|---|---|---|
