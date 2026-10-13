# Test log

## How to run the tests

With the app running (`docker compose up`):

```bash
docker compose exec backend php artisan test   # backend: 109 tests
docker compose exec frontend npm test          # website: 91 tests
```

- Backend tests run against MySQL, in their own database (`workwize_test`), so they never touch the app's data. A safety check stops them if they're ever pointed at another database.
- Backend tests always use the real Jamf mock file. Edge cases use small copies of it with one change each, made inside the test; the original file is never edited.
- Website tests run in a fixed time zone (Amsterdam), so dates and times give the same result on any computer.

## How we check that a test can fail

A test that has never failed proves little. For every rule we add, we switch the rule off on purpose ("deliberate break"), run the tests, confirm the right test fails, and put the rule back. So far 53 breaks, all caught. Examples: the "updated" count including our own timestamps, the delete warning for a person with laptops replaced by the short one, memory divided by 1000 instead of 1024, a lighter grey that fails the contrast check.

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

## How to read the results

- **Pass:** the actual result matched the expected one.
- **Fail:** it didn't; the "Fix" column says what was done or proposed.
- **Not built yet:** the feature is planned for a later step (see `DECISIONS.md`), so there's nothing to test yet.
- **Auto** = an automated test (name in "Where"); **Manual** = clicked through in the website, with a screenshot kept by the team.

## Every agreed scenario (T1–T32)

Test names starting with t1, t2, … match this list. Run on 13 Oct 2026 against the current code: backend 109 passed, website 91 passed.

| # | Scenario | Expected | Actual | Result | Where |
|---|---|---|---|---|---|
| T1 | First sync with the mock file | 7 laptops, 6 people, all assigned; "✓ First sync complete: 7 laptops and 6 people added." | As expected; recorded as the first sync | Pass | Auto: `SyncEngineTest`, `ApiTest`, `SyncHistoryTest`. Manual: Home |
| T2 | Sync the same file twice | No duplicates; second run "7 unchanged", nothing updated | As expected | Pass | Auto: `SyncEngineTest`, `ApiTest` |
| T3 | RAM 8 GB → 16 GB in Jamf | Updated, counted once | As expected | Pass | Auto: `SyncEngineTest` |
| T4 | Laptop reassigned to someone else | New owner; last owner updated | As expected | Pass | Auto: `SyncEngineTest` |
| T5 | Known laptop loses its owner | "To be collected from Emma Johnson"; Emma still exists | As expected | Pass | Auto: `SyncEngineTest`, `ScenarioFilesTest`. Manual: eventful scenario |
| T6 | New laptop without an owner | Skipped: "no owner in Jamf" | As expected | Pass | Auto: `SyncEngineTest` |
| T7 | To-be-collected laptop gets a new owner | Assigned again; label gone | As expected | Pass | Auto: `SyncEngineTest` |
| T8 | A known laptop is missing from the list | "No longer in Jamf · last seen with Lucas Brown"; not deleted | As expected | Pass | Auto: `SyncEngineTest`. Manual: eventful scenario |
| T9 | A missing laptop comes back | Assigned again, or "To be collected" if it has no owner | As expected | Pass | Auto: `SyncEngineTest` |
| T10 | Delete a laptop, then sync | It comes back | As expected | Pass | Auto: `SyncEngineTest`, `ApiTest` |
| T11 | Delete a to-be-collected or missing laptop, then sync | It doesn't come back | As expected | Pass | Auto: `SyncEngineTest` |
| T12 | Delete a person with 2 laptops | Person gone; laptops "Owner removed in WorkWize", never "To be collected" | As expected | Pass | Auto: `ApiTest` |
| T13 | Sync after deleting a person | Person back, laptops linked again | As expected | Pass | Auto: `SyncEngineTest`, `ApiTest` |
| T14 | Missing file, broken JSON, no results list | Plain error; nothing changes; the failed attempt is recorded | As expected | Pass | Auto: `SyncEngineTest`, `ApiTest`, `SyncHistoryTest` |
| T15 | An empty list | Every laptop "No longer in Jamf", none deleted; the message says Jamf's list was empty | As expected | Pass | Auto: `SyncEngineTest`, website `syncMessage`. Manual: empty scenario |
| T16 | The next normal sync after that | All flags cleared | As expected | Pass | Auto: `SyncEngineTest`. Manual |
| T17 | Phone removed in Jamf | Removed here too, shown as "Not provided by Jamf" | As expected | Pass | Auto: `SyncEngineTest`, website `format` |
| T18 | Person without a name | Name = email | As expected | Pass | Auto: `SyncEngineTest` |
| T19 | Laptop without a name | Name = model | As expected | Pass | Auto: `SyncEngineTest` |
| T20 | Mac mini without a battery value | Stored empty, no crash; "Not provided by Jamf" | As expected | Pass | Auto: `SyncEngineTest`, website `AssetDetailPage` |
| T21 | Entry without a serial | Skipped with the reason; the rest synced | As expected | Pass | Auto: `SyncEngineTest` |
| T22 | Invalid email for a known laptop | Skipped; laptop unchanged and not flagged as missing | As expected | Pass | Auto: `SyncEngineTest` |
| T23 | Same serial twice | Conflicting: nothing changes, both reported. Identical: used normally | As expected | Pass | Auto: `SyncEngineTest` |
| T24 | Messy email and serial (case, spaces around) | Matched to the existing records | As expected | Pass | Auto: `SyncEngineTest` |
| T25 | A second sync while one is running | Refused with "A sync is already running", recorded, nothing changed | As expected; also checked with two real processes | Pass | Auto: `SyncLockTest`. Manual |
| T26 | A laptop 40 days behind the newest check-in | A "hasn't checked in" warning | – | Not built yet | Planned (`DECISIONS.md` row 13) |
| T27 | The sync message | Laptops that newly need attention first, by name; zeros hidden; skip reasons; "Show details" | As expected | Pass | Auto: `SyncEngineTest`, website `syncMessage`, `AssetsPage`. Manual |
| T28 | "Needs attention (n)" button | Filters the list; stays after closing the message; disappears at 0 | As expected on the laptops list; the people list's button is not built yet | Pass (laptops) · Not built yet (people) | Auto: `ApiTest`, website `AssetsPage`. Manual |
| T29 | People list: badges and "No laptops" | "No laptops" instead of 0; badges for laptops to collect | "No laptops" as expected (Emma and Lucas after the eventful scenario); badges not built yet | Pass (No laptops) · Not built yet (badges) | Auto: website `EmployeesPage`. Manual |
| T30 | Home before the first sync, after it, and after an eventful sync | Empty-state text; totals; a list of items that need attention | Empty state and totals as expected; the attention list on Home is not built yet | Pass (empty, totals) · Not built yet (attention list) | Auto: website `HomePage`. Manual |
| T31 | Sync history | Every run kept, including failed and refused; a page to view it | Every run kept (checked in the database: first sync, eventful, empty list, normal); the page is not built yet | Pass (record) · Not built yet (page) | Auto: `SyncHistoryTest`, `SyncLockTest` |
| T32 | ⓘ explanations | On statuses, badges and fields | On every status that needs a person (list, laptop page, message); fields and badges not built yet | Pass (statuses) · Not built yet (fields, badges) | Auto: website `statusInfo`, `AssetsPage`. Manual |

## Other things the tests cover

- **All or nothing:** a forced crash halfway through a sync saves nothing, but the failed attempt is recorded (`SyncEngineTest`, `SyncHistoryTest`).
- **Sync history:** every attempt is recorded with its result, counts, skips and the reason if it failed; the first successful sync is marked (`SyncHistoryTest`).
- **The Jamf reader:** count check (`totalCount`), missing `totalCount`, empty list, broken file (`JamfProviderTest`).
- **The translator:** all 7 mock laptops field by field, plus edge cases (`JamfDeviceMapperTest`).
- **The API:** no editing anywhere (6 kinds of edit attempts refused with a plain message), unknown records, the health check, the `mdm:sync` command failing clearly (`ApiTest`, `HealthAndRootTest`, `SyncEngineTest`).
- **The website:** every page's normal, empty and error state; the sync message wording and its attention list, "Show details" and ⓘ explanations; delete confirmations; the dialog with the keyboard; singular and plural; local times; colour contrast (WCAG AA).

## Manual checks

| Check | Result |
|---|---|
| Fresh clone → `docker compose up` (all Docker images removed first) | Working website in 86 seconds; every README command works |
| Click-through of every screen, including the scenario files in `backend/tests/Fixtures/jamf/` | All screens as expected. Found and fixed: Docker settings not reaching the website, times in UTC, an out-of-date message next to a new one |
| Phone width (375 px) | Fixed: status labels used to cover the laptop names |
| Keyboard only | Fixed: the delete dialog now keeps focus inside and returns it when closed |
| Two processes at once: one holds the sync lock, the website presses Sync Now | HTTP 409 "A sync is already running"; after release the next sync works; both attempts in the history |
| Tier 2 click-through with the scenario files (`eventful.json`, `empty.json`) | First-sync message; the full message with one laptop to collect and one no longer in Jamf; "Show details"; ⓘ explanations; the "Needs attention" button and filter; "already running"; an empty list flags all 7 and deletes none; the next normal sync clears every flag |
