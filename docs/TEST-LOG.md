# Test log

## How to run the tests

With the app running (`docker compose up`):

```bash
docker compose exec backend php artisan test   # backend: 117 tests
docker compose exec frontend npm test          # website: 92 tests
```

- Backend tests run against MySQL, in their own database (`workwize_test`), so they never touch the app's data. A safety check stops them if they're ever pointed at another database.
- Backend tests always use the real Jamf mock file. Edge cases use small copies of it with one change each, made inside the test; the original file is never edited.
- Website tests run in a fixed time zone (Amsterdam), so dates and times give the same result on any computer.

## How we check that a test can fail

A test that has never failed proves little. For every rule we add, we switch the rule off on purpose ("deliberate break"), run the tests, confirm the right test fails, and put the rule back. So far 55 breaks, all caught. Examples: the "updated" count including our own timestamps, the delete warning for a person with laptops replaced by the short one, memory divided by 1000 instead of 1024, a lighter grey that fails the contrast check.

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
- **Auto** = an automated test (name in "Where"); **Manual** = clicked through in the website.
- **Screenshot** names the picture that proves a manual check. The pictures are kept by the team for now and will be added with the final documentation. "None" means no picture exists for that check.
- Each scenario starts with what it means for the business, in bold.
- Findings (in the sections below) also name, in plain words, the design principle they break (for example "never lose data silently"). The full list of principles will be published with the final documentation.

## Every agreed scenario (T1–T32)

Test names starting with t1, t2, … match this list. Run on 13 Oct 2026 against the current code: backend 109 passed, website 91 passed.

| # | Scenario | Expected | Actual | Result | Where | Screenshot |
|---|---|---|---|---|---|---|
| T1 | **A company connects WorkWize to Jamf for the first time.** First sync with the mock file | 7 laptops, 6 people, all assigned; "✓ First sync complete: 7 laptops and 6 people added." | As expected; recorded as the first sync | Pass | Auto: `SyncEngineTest`, `ApiTest`, `SyncHistoryTest`. Manual: Home | `t2-01-first-sync-message`, `p5-02-t30-home-after-first-sync` |
| T2 | **The daily sync runs and nothing has changed.** Sync the same file twice | No duplicates; second run "7 unchanged", nothing updated | As expected | Pass | Auto: `SyncEngineTest`, `ApiTest` | None: covered by automated tests only (no screenshot of a "7 unchanged" sync) |
| T3 | **IT upgrades a laptop's memory.** RAM 8 GB → 16 GB in Jamf | Updated, counted once | As expected | Pass | Auto: `SyncEngineTest` | – |
| T4 | **A laptop is handed from Alex to Emma.** Laptop reassigned to someone else | New owner; last owner updated | As expected | Pass | Auto: `SyncEngineTest` | – |
| T5 | **Emma leaves the company; IT removes her as the laptop's owner.** Known laptop loses its owner | "To be collected from Emma Johnson"; Emma still exists | As expected | Pass | Auto: `SyncEngineTest`, `ScenarioFilesTest`. Manual: eventful scenario | `t2-02-eventful-sync-full-box`, `09b-laptop-details-no-current-owner-v2` |
| T6 | **IT enrols a spare laptop that nobody uses yet.** New laptop without an owner | Skipped: "no owner in Jamf" | As expected | Pass | Auto: `SyncEngineTest` | – |
| T7 | **Emma's old laptop is given to a new starter.** To-be-collected laptop gets a new owner | Assigned again; label gone | As expected | Pass | Auto: `SyncEngineTest` | – |
| T8 | **A laptop disappears from Jamf (retired, lost, stolen or a Jamf error).** A known laptop is missing from the list | "No longer in Jamf · last seen with Lucas Brown"; not deleted | As expected | Pass | Auto: `SyncEngineTest`. Manual: eventful scenario | `t2-02-eventful-sync-full-box`, `t2-05-needs-attention-button-filtered-list` |
| T9 | **That laptop turns up in Jamf again.** A missing laptop comes back | Assigned again, or "To be collected" if it has no owner | As expected | Pass | Auto: `SyncEngineTest` | – |
| T10 | **Someone deletes a laptop in WorkWize by mistake.** Delete a laptop, then sync | It comes back | As expected | Pass | Auto: `SyncEngineTest`, `ApiTest` | `07a-delete-laptop-confirmation`, `07b-delete-laptop-gone`, `07c-delete-laptop-back-after-sync-v2` |
| T11 | **A collected or retired laptop is cleaned up in WorkWize.** Delete a to-be-collected or missing laptop, then sync | It doesn't come back | As expected | Pass | Auto: `SyncEngineTest` | – |
| T12 | **Someone deletes Alex, who still has two laptops.** Delete a person with 2 laptops | Person gone; laptops "Owner removed in WorkWize", never "To be collected" | As expected | Pass | Auto: `ApiTest` | `08a-delete-person-d3-warning`, `08b-delete-person-laptops-owner-removed` |
| T13 | **The next sync after deleting Alex.** Sync after deleting a person | Person back, laptops linked again | As expected | Pass | Auto: `SyncEngineTest`, `ApiTest` | `08c-delete-person-back-after-sync-v2` |
| T14 | **Jamf's export is missing or broken.** Missing file, broken JSON, no results list | Plain error; nothing changes; the failed attempt is recorded | As expected | Pass | Auto: `SyncEngineTest`, `ApiTest`, `SyncHistoryTest` | `10-failed-sync-broken-file` |
| T15 | **Jamf sends an empty list by mistake.** An empty list | Every laptop "No longer in Jamf", none deleted; the message says Jamf's list was empty | As expected | Pass | Auto: `SyncEngineTest`, website `syncMessage`. Manual: empty scenario | `p5-04-t15-empty-list-message` |
| T16 | **Jamf is back to normal the next day.** The next normal sync after that | All flags cleared | As expected | Pass | Auto: `SyncEngineTest`. Manual | `t2-08-next-sync-clears-flags` |
| T17 | **Alex asks for his phone number to be removed (privacy).** Phone removed in Jamf | Removed here too, shown as "Not provided by Jamf" | As expected | Pass | Auto: `SyncEngineTest`, website `format` | – |
| T18 | **Jamf has a person without a name.** Person without a name | Name = email | As expected | Pass | Auto: `SyncEngineTest` | – |
| T19 | **Jamf has a laptop without a name.** Laptop without a name | Name = model | As expected | Pass | Auto: `SyncEngineTest` | – |
| T20 | **A desktop Mac mini has no battery.** Mac mini without a battery value | Stored empty, no crash; "Not provided by Jamf" | As expected | Pass | Auto: `SyncEngineTest`, website `AssetDetailPage` | – |
| T21 | **One laptop in Jamf has no serial number.** Entry without a serial | Skipped with the reason; the rest synced | As expected | Pass | Auto: `SyncEngineTest` | – |
| T22 | **Someone mistypes Emma's email in Jamf.** Invalid email for a known laptop | Skipped; laptop unchanged and not flagged as missing | As expected | Pass | Auto: `SyncEngineTest` | – |
| T23 | **Jamf lists the same laptop twice.** Same serial twice | Conflicting: nothing changes, both reported. Identical: used normally | As expected | Pass | Auto: `SyncEngineTest` | – |
| T24 | **Jamf data typed with odd capitals and spaces.** Messy email and serial (case, spaces around) | Matched to the existing records | As expected | Pass | Auto: `SyncEngineTest` | – |
| T25 | **Two people press Sync Now at the same time.** A second sync while one is running | Refused with "A sync is already running", recorded, nothing changed | As expected; also checked with two real processes | Pass | Auto: `SyncLockTest`. Manual | `t2-06-already-running` |
| T26 | **A laptop hasn't been switched on for weeks.** A laptop 40 days behind the newest check-in | A "hasn't checked in" warning | – | Not built yet | Planned (`DECISIONS.md` row 13) | – |
| T27 | **IT reads what changed after a sync.** The sync message | Laptops that newly need attention first, by name; zeros hidden; skip reasons; "Show details" | As expected | Pass | Auto: `SyncEngineTest`, website `syncMessage`, `AssetsPage`. Manual | `t2-02-eventful-sync-full-box`, `t2-03-show-details-opened`, `t2-04-info-text-opened` |
| T28 | **IT wants to see only what still needs doing.** "Needs attention (n)" button | Filters the list; stays after closing the message; disappears at 0 | As expected on the laptops list; the people list's button is not built yet | Pass (laptops) · Not built yet (people) | Auto: `ApiTest`, website `AssetsPage`. Manual | `t2-05-needs-attention-button-filtered-list` |
| T29 | **HR checks who has which laptops.** People list: badges and "No laptops" | "No laptops" instead of 0; badges for laptops to collect | "No laptops" as expected (Emma and Lucas after the eventful scenario); badges not built yet | Pass (No laptops) · Not built yet (badges) | Auto: website `EmployeesPage`. Manual | `p5-03-t29-employees-no-laptops` |
| T30 | **IT opens WorkWize in the morning.** Home before the first sync, after it, and after an eventful sync | Empty-state text; totals; a list of items that need attention | Empty state and totals as expected; the attention list on Home is not built yet | Pass (empty, totals) · Not built yet (attention list) | Auto: website `HomePage`. Manual | `p5-01-t30-home-before-first-sync`, `p5-02-t30-home-after-first-sync` |
| T31 | **IT checks when the last sync ran and what it did.** Sync history | Every run kept, including failed and refused; a page to view it | Every run kept (checked in the database: first sync, eventful, empty list, normal); the page is not built yet | Pass (record) · Not built yet (page) | Auto: `SyncHistoryTest`, `SyncLockTest` | None: checked in the database, there's no screen for it yet |
| T32 | **Someone doesn't understand a status.** ⓘ explanations | On statuses, badges and fields | On every status that needs a person (list, laptop page, message); fields and badges not built yet | Pass (statuses) · Not built yet (fields, badges) | Auto: website `statusInfo`, `AssetsPage`. Manual | `t2-04-info-text-opened` |

## Odd data (testing 5.2)

Real Jamf exports are messier than the 7-laptop mock. Each case below was put through the real sync engine in the test database. **Prediction** is what we expected before running it, kept exactly as written in the plan, even when it turned out wrong. Cases that behaved correctly were kept as permanent tests (`OddDataTest`).

| # | Case | Prediction (before running) | Actual | Result | Prediction | Principle at stake | Finding |
|---|---|---|---|---|---|---|---|
| O1 | **Someone types a very long laptop name in Jamf.** Text over the database's 255-character limit: laptop name, person name, job title, email, serial (300 characters each, one at a time) | The whole sync fails ("Something went wrong while saving"): one bad laptop blocks every sync | Laptop name, person name, job title, serial: **the whole sync fails**, nothing is imported for anyone. Email: the sync succeeds; that laptop is skipped as "invalid email" (PHP's email check already rejects emails that long). Also seen: the technical reason sent back to the browser in the API's `error` field includes internal details (database host, port, database name) | Fail | Right for 4 of 5; wrong for email (what we learned: the email check already guards that field) | Design for the everyday customer: one rare entry must not stop everyone; one broken entry is skipped, not the whole list | F1 (long text blocks the sync) · F2 (internal details in the API's error field) |
| O2 | **Another tool writes numbers as text in the export.** RAM `"16384"`, cores `"10"`, battery `"91"` instead of 16384, 10, 91 | Saved as empty, shown as "Not provided by Jamf" although Jamf did provide them | Exactly that: the sync succeeds, but memory, cores and battery are stored empty, so the laptop page says "Not provided by Jamf" for values Jamf did send (screenshot `p5-odd-02-numbers-as-text`) | Fail | Right | The data must stay honest; never say something that's only sometimes true | F3 (product question) |
| O3 | **An export tool writes the laptop count as text.** `"totalCount": "7"` instead of 7 | The whole list is rejected as unreadable although all 7 laptops are fine | Exactly that: "Couldn't read the Jamf list. Nothing was changed." (reason: invalid `totalCount`), nothing imported | Fail | Right | Design for the everyday customer: a harmless format difference must not stop every sync | F4 |
| O4 | **Someone types a serial with a space in Jamf.** `C02 DL0XYZQ6N` instead of `C02DL0XYZQ6N` (after a normal sync) | A second record for the same laptop; the real one flagged "No longer in Jamf" | Exactly that: a new "Alex’s MacBook Pro" with the spaced serial, assigned to Alex; the real one marked "No longer in Jamf" and listed as needing attention | Fail | Right | One laptop is one record; the data must stay honest | F5 (product question) |
| O5 | **People with accented names get accented email addresses.** `jose@exämple.com` (accented domain), `josé@company.test`, `mia+laptop@company.test`, `noah miller@company.test` (space inside) | The accented ones and the space are skipped as "invalid email"; the `+` one works | Exactly that: both accented emails and the one with a space are skipped as "invalid email" (shown with the reason); `mia+laptop@company.test` is imported normally | Pass for `+` and the space · Fail for accented emails | Right | The data must stay honest (a real person with a valid international email is left out) | F6 (product question) |
| O6 | **Jamf leaves out parts of a laptop's record.** No `hardware` section; no `general` section; no owner section; `general` set to `null`; `hardware` written as text | Skipped "no serial number" / name falls back to the model / no owner / treated as missing / skipped "incomplete information". No crash | Exactly that: no hardware → skipped "no serial number"; text instead of hardware → skipped "incomplete information"; no owner section → a new laptop without owner, skipped "no owner in Jamf"; no `general` or `null` → the laptop is imported and named after its model (e.g. "Mac mini (M1, 2020)"). No crash; the other laptops synced | Pass | Right | Never lose data silently; one broken entry is skipped with a reason | – |
| O7 | **Names with emoji.** "Design 💻 MacBook", "Lucas 🚀 Brown" | Stored and shown correctly | Stored and shown correctly in the list and labels (screenshot `p5-odd-07-09-emoji-rtl-html`) | Pass | Right | – | – |
| O8 | **Names in a right-to-left language.** Person "محمد غزوان", laptop "حاسوب الدعم" | Stored correctly. On screen, "Assigned to {name}" may show its punctuation in an odd order. Display only | Stored correctly; "Assigned to محمد غزوان" reads correctly on screen, nothing out of order (same screenshot) | Pass | Wrong (what we learned: the browser handles mixed-direction text well in these short labels) | – | – |
| O9 | **Security: a name that looks like web code.** Laptop name `<img src=x onerror=alert(1)>` | Shown as plain text, never run as code | Shown as plain text; checked in the page: no image element was created and nothing ran (same screenshot) | Pass | Right | – | – |
| O10 | **Absurd numbers in Jamf.** RAM 999,999,999,999 MB, cores 10¹⁵, a RAM value too big for the computer (10³⁰) | Stored and shown as Jamf sent it; the too-big one becomes empty | Exactly that: "976562500 GB" and 1000000000000000 cores are shown as Jamf sent them; the 10³⁰ value is stored empty ("Not provided by Jamf"). No crash | Pass | Right | – | – |
| O11 | **Impossible values in Jamf.** Battery 140%, RAM −8192 MB | Empty ("Not provided by Jamf"). Slightly untrue, because Jamf did send something | Exactly that (screenshot `p5-odd-11-12-battery-140-and-date-05-01`) | Pass, with a note | Right | Never say something that's only sometimes true (mildly: Jamf did send a value, just an impossible one) | F8 (limitation) |
| O12 | **Dates written in other formats.** Without time zone, date only, another time zone, a timestamp number, `"05/01/2026"`, empty, nonsense; purchase date `15/06/2022` | Most handled. The timestamp number becomes empty. `"05/01/2026"` may be silently read as 1 May instead of 5 January | Without time zone, date only and another time zone: read correctly. Timestamp number, empty and nonsense: empty. **`"05/01/2026"` is read as 1 May 2026** and shown as "1 May 2026" on the laptop page (same screenshot). Purchase date `15/06/2022`: empty | Fail for `"05/01/2026"` · Pass for the rest | Right; the purchase-date format wasn't predicted (what we learned: purchase dates only accept year-month-day) | The data must stay honest (a wrong date shown as if true) | F7 (product question) |
| O13 | **The export was saved by a Windows tool that adds an invisible character at the start of the file** (the "byte order mark") | The whole list is rejected as unreadable although the data is fine | Exactly that: "Couldn't read the Jamf list. Nothing was changed." (reason: not valid JSON), nothing imported | Fail | Right | Design for the everyday customer: an invisible, harmless character must not stop every sync | F9 |
| O14 | **Jamf sends its ID as a number.** `101` instead of `"101"` | Works | Works: stored as "101" | Pass | Right | – | – |
| O15 | **Jamf adds fields WorkWize doesn't know.** Extra sections such as `extensionAttributes` | Ignored | Ignored; the laptop synced normally | Pass | Right | – | – |
| O16 | **The same person, typed with other capitals and another name, on their second laptop.** `ALEX.SMITH@Company.TEST`, "Alexander Smith" | One person; the first entry's details are used | One person; name stays "Alex Smith" from the first entry | Pass | Right | – | – |
| O17 | **Names that are only spaces.** Person name and laptop name `"   "` | Fall back (email or model) | Person name = their email; laptop name = its model ("Mac mini (M1, 2020)") | Pass | Right | – | – |
| O18 | **Line breaks or tabs inside a name.** `Finance⏎Team⇥MacBook` | Stored; shown on one line | Stored exactly as sent. On screen, browsers show line breaks and tabs as spaces; not checked with a screenshot | Pass | Right (screen part not separately checked) | – | – |
| O19 | **Jamf promises more laptops than it sends.** `totalCount` 10, 7 sent | Rejected as incomplete (correct by design, re-confirmed) | Rejected: "Jamf list is incomplete: expected 10 devices, got 7." Nothing changed | Pass | Right | – | – |

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

## Findings

Problems found while testing, in plain words. The category follows our rule: **fix proposal** if it can block a sync for everyone, leave the app stuck, or show something untrue; **limitation** if it's only speed or rare odd data; **product question** if it needs a decision about how WorkWize should behave. Nothing here has been fixed yet; fixes come after approval.

| # | Finding | What it means for a customer | Principle it breaks | Category | Status |
|---|---|---|---|---|---|
| F1 | A laptop name, person name, job title or serial longer than 255 characters makes the whole sync fail | One laptop with an over-long name in Jamf stops every sync for the whole company until someone shortens it in Jamf | Design for the everyday customer: one rare entry must not stop everyone; one broken entry is skipped, not the whole list | Fix proposal | Waiting for approval |
| F2 | When a sync fails while saving, the API's `error` field includes internal details (database host, port, database name) | Not shown on screen, but visible to anyone who looks at the website's network traffic | Give context and instructions, not raw technical errors (and basic security hygiene) | To decide (outside the three categories: security) | Waiting for approval |
| F3 | Numbers sent as text (`"16384"`) are treated as missing | The laptop page says "Not provided by Jamf" for memory, cores or battery that Jamf did send, just in a different format | The data must stay honest | Product question | Waiting for a decision |
| F4 | A laptop count written as text (`"7"`) makes the whole list unreadable | Every sync fails although the laptops themselves are fine | Design for the everyday customer | Fix proposal | Waiting for approval |
| F5 | A serial with a space inside counts as a different laptop | The same laptop appears twice, and the real one is falsely reported as missing from Jamf | One laptop is one record; honest data | Product question | Waiting for a decision |
| F6 | Emails with accents (international addresses, e.g. `josé@…`, `…@exämple.com`) are rejected as invalid | That person's laptops aren't imported; the sync message shows them as skipped with "invalid email" | Honest data | Product question | Waiting for a decision |
| F7 | A date like `"05/01/2026"` is read the American way (1 May), not the European way (5 January); a purchase date like `15/06/2022` is ignored | The laptop page can show a wrong last check-in date as if it were true. (Jamf's real API sends dates as `2026-01-05T10:30:12Z`, so this only matters for hand-made or converted exports) | Honest data | Product question | Waiting for a decision |
| F8 | Impossible values (battery 140%, negative memory) are shown as "Not provided by Jamf" | Rare; the label is slightly untrue because Jamf did send a value, just a nonsense one | Never say something that's only sometimes true (mildly) | Limitation | To be listed in IDEAS-AND-LIMITATIONS |
| F9 | An invisible character at the start of the file (added by some Windows tools) makes the whole list unreadable | Every sync fails although the data is fine; the customer can't see what's wrong | Design for the everyday customer | Fix proposal | Waiting for approval |
