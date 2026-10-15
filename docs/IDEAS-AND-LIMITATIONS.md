# Ideas, inefficiencies and known limitations

## Known limitations / shortcuts
| Limitation | Why we accepted it | What we'd do with more time |
|---|---|---|
| The backend runs on Laravel's built-in development server, and passwords in `docker-compose.yml` are local-only values | The brief asks for a local development setup only | A production web server (e.g. Nginx + PHP-FPM) and secrets kept outside the repo |
| If Jamf ever sends an empty list by mistake, every laptop is flagged "No longer in Jamf" until the next good sync | Nothing is deleted and the next good sync clears the flags; refusing short lists would also block real retirements (`DECISIONS.md` row 9) | Ask before applying a sync that would flag most laptops ("This sync would flag 5 of 7 laptops. Apply anyway?") |
| The sync looks up each laptop and person one at a time | Measured in testing 5.3: about 3 database queries per laptop; 5,000 laptops sync in about 3 seconds, 12,000 in about 7. Fast enough for a mid-size customer | Look up all existing laptops and people in one go before saving |
| The laptops and people lists come back whole (no paging, no search) | Measured in testing 5.3: 5,000 laptops appear in about a second (the list itself is 848 KB and takes 0.19 s), but the page is about 380 screens long, so finding one laptop means scrolling. The "Needs attention" filter still works as a shortcut | Page the API lists and the screens; add search and filters |
| Pagination isn't implemented because the mock is one page; the count check is already written for the multi-page case | The brief provides a single-page mock file | Fetch every page from the real Jamf API, then run the same count check |
| Laravel's standard `users` table exists but isn't used (no login) | Kept Laravel's defaults; its `cache` tables will hold the "sync already running" lock | Add accounts and login, or remove the table |
| The sync reads the whole Jamf file into memory, so it fails above about 12,000 laptops: 12,000 synced in about 7 seconds, 13,000 ran out of memory (testing 5.3, finding F15). Nothing is saved, and since the fixes the customer sees "Something went wrong during the sync. Nothing was changed." | Muhammad decided to keep it a limitation: far above a mid-size customer (5,000 laptops sync in about 3 seconds), and a live Jamf connection reads page by page anyway. The 30-second time limit is never the problem first | Process the list page by page |
| A person's name, job title or phone sent in a form WorkWize can't use (e.g. a list instead of text) is left blank; the "couldn't read" label only covers laptop fields | Extremely rare; Muhammad chose to keep the label to the laptop page | Store and show unreadable person details the same way |
| After a very large change, the sync message lists every laptop that needs attention (6,871 lines in testing 5.4, finding F14) | Muhammad decided to keep it a limitation: only at large scale, and the message is still true | Show the first 10, then "and 6,861 more" linking to the Needs attention filter |

## Improvement ideas
| Idea | Who benefits | Effort |
|---|---|---|
| Thousands separators: "4,897 unchanged" instead of "4897" (testing 5.3) | Larger customers, who read these numbers every day | Small |
| An alert (email or Slack) when a sync is interrupted or fails (testing 5.3, 5.4). Since fix F10 the history marks it "interrupted", but nobody is told | IT teams, who would otherwise only notice on the next visit | Medium |

## Inefficiencies noticed
| Where | What | Fix idea |
|---|---|---|
