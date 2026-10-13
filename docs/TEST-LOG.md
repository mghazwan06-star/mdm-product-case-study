# Test log

## How to run the tests

With the app running (`docker compose up`):

```bash
docker compose exec backend php artisan test   # backend: 96 tests
docker compose exec frontend npm test          # website: 80 tests
```

- Backend tests run against MySQL, in their own database (`workwize_test`), so they never touch the app's data. A safety check stops them if they're ever pointed at another database.
- Backend tests always use the real Jamf mock file. Edge cases use small copies of it with one change each, made inside the test; the original file is never edited.
- Website tests run in a fixed time zone (Amsterdam), so dates and times give the same result on any computer.

## How we check that a test can fail

A test that has never failed proves little. For every rule we add, we switch the rule off on purpose ("deliberate break"), run the tests, confirm the right test fails, and put the rule back. So far 40 breaks, all caught. Examples: the "updated" count including our own timestamps, the delete warning for a person with laptops replaced by the short one, memory divided by 1000 instead of 1024, a lighter grey that fails the contrast check.

## Brief rules → tests

| Brief rule | Proved by |
|---|---|
| Asset unique by serial | `DatabaseSchemaTest`: a serial can only be stored once (ignoring case); `SyncEngineTest` t2, t24 |
| Employee unique by email | `DatabaseSchemaTest`: email stored once, ignoring case; `SyncEngineTest` t24 |
| Unassigned devices not imported | `SyncEngineTest` t6 |
| Device details visible after sync | `ApiTest`: laptop details show every field; website `AssetDetailPage` tests |
| Missing employee is created | `SyncEngineTest` t1 |
| Jamf is the source of truth for assignments | `SyncEngineTest` t4, t5, t7 |
| Re-running sync recreates deleted assets and employees | `SyncEngineTest` t10, t13; `ApiTest` t10, t13 |
| Changes in Jamf are reflected (e.g. RAM) | `SyncEngineTest` t3, t17 |
| The website only uses the API | Website page tests use a fake API; nothing in `frontend/` reads the Jamf file |
| The backend reads the mock JSON directly | `JamfProviderTest`: the real mock file hands over seven laptops |
| Easy to add another MDM tool | `SyncEngineTest`: engine works for another provider with its own wording (a made-up "Intune") |

## Numbered scenarios

Test names starting with t1, t2, … match this list. Gaps are scenarios for features that are planned but not built yet (see `DECISIONS.md`).

| # | Scenario | Expected | Where |
|---|---|---|---|
| t1 | First sync with the mock file | 7 laptops, 6 people, all assigned; recorded as the first sync | `SyncEngineTest`, `ApiTest`, `SyncHistoryTest` |
| t2 | Sync the same file twice | No duplicates; second run "7 unchanged", nothing updated | `SyncEngineTest`, `ApiTest` |
| t3 | RAM 8 GB → 16 GB in Jamf | Updated, counted once | `SyncEngineTest` |
| t4 | Laptop reassigned to someone else | New owner; last owner updated | `SyncEngineTest` |
| t5 | Known laptop loses its owner | "To be collected from Emma Johnson"; Emma still exists | `SyncEngineTest` |
| t6 | New laptop without an owner | Skipped: "no owner in Jamf" | `SyncEngineTest` |
| t7 | To-be-collected laptop gets a new owner | Assigned again | `SyncEngineTest` |
| t10 | Delete a laptop, then sync | It comes back | `SyncEngineTest`, `ApiTest` |
| t11 | Delete a to-be-collected laptop, then sync | It doesn't come back | `SyncEngineTest` |
| t12 | Delete a person with 2 laptops | Person gone; both laptops "Owner removed in WorkWize", never "To be collected" | `ApiTest` |
| t13 | Sync after deleting a person | Person back, laptops linked again | `SyncEngineTest`, `ApiTest` |
| t14 | Missing file, broken JSON, no results list | Plain error; nothing in the database changes; the failed attempt is recorded | `SyncEngineTest`, `ApiTest`, `SyncHistoryTest` |
| t17 | Phone removed in Jamf | Removed here too | `SyncEngineTest` |
| t18, t19 | No person name / no laptop name | Name = email / name = model | `SyncEngineTest` |
| t20 | Mac mini without battery value | Stored empty, no crash | `SyncEngineTest` |
| t21 | Entry without a serial | Skipped with the reason; the rest synced | `SyncEngineTest` |
| t22 | Invalid email for a known laptop | Skipped; laptop unchanged | `SyncEngineTest` |
| t23 | Same serial twice | Conflicting: nothing changes, both reported. Identical: used normally | `SyncEngineTest` |
| t24 | Messy email and serial (case, spaces) | Matched to the existing records | `SyncEngineTest` |
| t25 | A second sync while one is running | Refused with "A sync is already running", recorded, nothing changed | `SyncLockTest` |

Not built yet, so not tested yet: t8, t9, t15, t16 (laptops missing from Jamf), t26 (stale check-in warning).

## Other things the tests cover

- **All or nothing:** a forced crash halfway through a sync saves nothing, but the failed attempt is recorded (`SyncEngineTest`, `SyncHistoryTest`).
- **Sync history:** every attempt is recorded with its result, counts, skips and the reason if it failed; the first successful sync is marked (`SyncHistoryTest`).
- **The Jamf reader:** count check (`totalCount`), missing `totalCount`, empty list, broken file (`JamfProviderTest`).
- **The translator:** all 7 mock laptops field by field, plus edge cases (`JamfDeviceMapperTest`).
- **The API:** no editing anywhere (6 kinds of edit attempts refused with a plain message), unknown records, the health check, the `mdm:sync` command failing clearly (`ApiTest`, `HealthAndRootTest`, `SyncEngineTest`).
- **The website:** every page's normal, empty and error state; the sync message wording; delete confirmations; the dialog with the keyboard; singular and plural; local times; colour contrast (WCAG AA).

## Manual checks

| Check | Result |
|---|---|
| Fresh clone → `docker compose up` (all Docker images removed first) | Working website in 86 seconds; every README command works |
| Click-through of every screen, including the scenario files in `backend/tests/Fixtures/jamf/` | All screens as expected. Found and fixed: Docker settings not reaching the website, times in UTC, an out-of-date message next to a new one |
| Phone width (375 px) | Fixed: status labels used to cover the laptop names |
| Keyboard only | Fixed: the delete dialog now keeps focus inside and returns it when closed |
| Two processes at once: one holds the sync lock, the website presses Sync Now | HTTP 409 "A sync is already running"; after release the next sync works; both attempts in the history |
