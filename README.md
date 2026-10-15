# WorkWize MDM Device Sync

A Laravel + React app that imports assigned laptops and their owners from Jamf (a mock API response) into MySQL, and shows them in a website.

- The assignment brief from WorkWize: [BRIEF.md](BRIEF.md)
- Our decisions and assumptions, with what's built and what's designed but not built: [docs/DECISIONS.md](docs/DECISIONS.md)

## For reviewers: start here (about 10 minutes)

1. **How this was designed** and **three product calls** (just below): the method and the thinking in two minutes.
2. [docs/PRINCIPLES.md](docs/PRINCIPLES.md): the 18 rules behind every decision, in Muhammad's words, with a table from each rule to the code and tests.
3. [docs/DECISIONS.md](docs/DECISIONS.md): every decision, why, which principles, and whether it's built.
4. [docs/TEST-LOG.md](docs/TEST-LOG.md): read "In one minute" at the top; pictures of the edge cases are further down.
5. [docs/MISTAKES.md](docs/MISTAKES.md): what went wrong and what we changed.

## How this was designed

- **Decisions first, code second.** Every product decision (what happens when someone leaves, when a laptop disappears from Jamf, when the Jamf list is broken, what the screens show) was made by Muhammad Ghazwan before any code was written. Muhammad is the product owner and doesn't have a coding background.
- **18 principles behind the decisions**, in his own words: [docs/PRINCIPLES.md](docs/PRINCIPLES.md). The main one: *Jamf decides what's true; WorkWize decides how to show it and what to do about it* (P1). Decisions name their principles, and so does the code where a principle becomes a rule.
- **AI was used openly.** claude.ai helped think through the options and reviewed every plan; Claude Code wrote the code and the tests. Every plan was approved by Muhammad before it was built, and every save point (commit) names Claude as co-author.
- **Tested and corrected.** The test log ([docs/TEST-LOG.md](docs/TEST-LOG.md)) covers the agreed scenarios, odd data, 1,000 to 25,000 laptops and crashes. What testing found, what Muhammad decided and what was fixed are all in the log. Mistakes, by every side, are in [docs/MISTAKES.md](docs/MISTAKES.md).

## Three product calls

- **Someone leaves the company.** The brief says not to import laptops without an owner. We read that as applying to *new* laptops: a known laptop that loses its owner in Jamf is kept and marked **"To be collected from {last owner}"**, because that's exactly when IT needs to act. ([DECISIONS.md](docs/DECISIONS.md) row 1; [P3](docs/PRINCIPLES.md#p3-never-lose-data-silently-prefer-flagging-plus-a-human-in-the-loop-over-deleting), [P4](docs/PRINCIPLES.md#p4-the-data-must-stay-honest-what-the-website-shows-must-match-reality), [P5](docs/PRINCIPLES.md#p5-a-status-should-lead-to-action))
- **A laptop disappears from Jamf.** It could be retired, lost, stolen or a Jamf error. Flagging it "No longer in Jamf" is safe in all four cases; deleting it isn't. A sync never deletes anything. (Row 2; [P3](docs/PRINCIPLES.md#p3-never-lose-data-silently-prefer-flagging-plus-a-human-in-the-loop-over-deleting), [P14](docs/PRINCIPLES.md#p14-things-clear-themselves-when-theyre-done))
- **Jamf sends something odd.** One broken entry is skipped with a reason instead of blocking everyone, and a value WorkWize can't read is shown as Jamf sent it, never guessed and never hidden. (Rows 10 and 18; [P2](docs/PRINCIPLES.md#p2-design-for-the-everyday-customer-make-the-worst-case-safe-and-fixable-dont-complicate-the-everyday-experience-for-rare-cases), [P4](docs/PRINCIPLES.md#p4-the-data-must-stay-honest-what-the-website-shows-must-match-reality), [P16](docs/PRINCIPLES.md#p16-never-make-a-gap-look-like-our-mistake-and-never-say-something-thats-only-sometimes-true))

## What's built, and what's designed but not built

Tier 1 (the brief) and Tier 2 (core improvements) are built and tested. Tier 3 (polish) and Tier 4 (stretch) were planned and fully designed, but not built because of time. Each has its decision, Muhammad's reasoning and its principles written down in [docs/DECISIONS.md](docs/DECISIONS.md), so it can be built as designed.

| Feature | Decided in | Principles | Status |
|---|---|---|---|
| Person page, with badges for laptops to collect or no longer in Jamf | Decision 6 (the employees page); [DECISIONS.md](docs/DECISIONS.md), Screens | [P5](docs/PRINCIPLES.md#p5-a-status-should-lead-to-action), [P17](docs/PRINCIPLES.md#p17-the-context-ladder-never-leave-something-blank-if-context-exists), [P18](docs/PRINCIPLES.md#p18-take-people-straight-to-the-problem-dont-make-them-search) | Designed, not built (time) |
| Home: the list of what needs attention, and the last sync time | Decision 6 (the home page); [DECISIONS.md](docs/DECISIONS.md), Screens | [P5](docs/PRINCIPLES.md#p5-a-status-should-lead-to-action), [P18](docs/PRINCIPLES.md#p18-take-people-straight-to-the-problem-dont-make-them-search) | Designed, not built (time) |
| "Needs attention" button and filter on the people list | Decision 6 (the sync message); [DECISIONS.md](docs/DECISIONS.md), Screens | [P14](docs/PRINCIPLES.md#p14-things-clear-themselves-when-theyre-done), [P18](docs/PRINCIPLES.md#p18-take-people-straight-to-the-problem-dont-make-them-search) | Designed, not built (time) |
| Sync history page (the history itself is recorded today) | Sync history decision; [DECISIONS.md](docs/DECISIONS.md) row 12 | [P4](docs/PRINCIPLES.md#p4-the-data-must-stay-honest-what-the-website-shows-must-match-reality) | Designed, not built (time) |
| "Hasn't checked in with Jamf for {n} days" warning (30 days, measured against the newest check-in in the list) | Decision 6 (the laptop page); [DECISIONS.md](docs/DECISIONS.md) row 13 | [P4](docs/PRINCIPLES.md#p4-the-data-must-stay-honest-what-the-website-shows-must-match-reality), [P5](docs/PRINCIPLES.md#p5-a-status-should-lead-to-action) | Designed, not built (time) |
| ⓘ on every field (today: on statuses that need a person and on "couldn't read") | Decision 6 (the laptop page); [DECISIONS.md](docs/DECISIONS.md), Screens | [P15](docs/PRINCIPLES.md#p15-context-everywhere-on-demand-the-info-symbol), [P17](docs/PRINCIPLES.md#p17-the-context-ladder-never-leave-something-blank-if-context-exists) | Designed, not built (time) |
| **Tier 4:** a PDF of any sync from the history page | Sync history decision, extended by Muhammad; scoped as a stretch goal | [P2](docs/PRINCIPLES.md#p2-design-for-the-everyday-customer-make-the-worst-case-safe-and-fixable-dont-complicate-the-everyday-experience-for-rare-cases) | Designed, not built (time) |

"Decision 6" is the screens decision, one of the six big product decisions made before any code (listed at the top of [PRINCIPLES.md](docs/PRINCIPLES.md)).

## What it looks like

**After a sync, what needs a person comes first**, by name and with a link: here a leaver's laptop to collect and a laptop that disappeared from Jamf.

![To be collected and No longer in Jamf](docs/screenshots/to-be-collected-and-no-longer-in-jamf.jpg)

**When Jamf sends something WorkWize can't use**, it says so and shows what Jamf sent, instead of guessing or hiding it.

![Jamf sent a value WorkWize couldn't read](docs/screenshots/f3-f7-f8-after-couldnt-read-label.jpg)

**When something breaks, the message is true and says what to do.** Before and after, from testing:

| Before | After |
|---|---|
| ![Raw database error](docs/screenshots/f12-before-raw-database-error.jpg) | ![Plain message](docs/screenshots/f12-after-plain-message.jpg) |
| ![Already running (untrue)](docs/screenshots/f10-before-already-running.jpg) | ![The last sync was interrupted](docs/screenshots/f10-after-interrupted.jpg) |

More pictures, next to each test case: [docs/TEST-LOG.md](docs/TEST-LOG.md).

## Start the app

You only need [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```bash
docker compose up
```

The first start takes a few minutes (Docker downloads MySQL, PHP and Node and installs the libraries). After that it starts in under a minute.

When it's ready, open:

| What | Address |
|---|---|
| Website | http://localhost:5173 |
| Backend health check | http://localhost:8000/api/health |

Press **Sync Now** on the website to import the laptops from the Jamf mock file.

## Stop or reset

```bash
docker compose down        # stop
docker compose down -v     # stop and delete the database (fresh start next time)
```

## Run the tests

With the app running:

```bash
docker compose exec backend php artisan test   # backend
docker compose exec frontend npm test          # website
```

What the tests cover, and how we check that they can fail: [docs/TEST-LOG.md](docs/TEST-LOG.md).

## Try a scenario

To see "To be collected", "No longer in Jamf", an empty list or a failed sync without editing WorkWize's mock file, start the backend with a scenario file, press Sync Now, then go back to normal. The scenarios are described in [backend/tests/Fixtures/jamf/README.md](backend/tests/Fixtures/jamf/README.md).

```bash
MDM_JAMF_MOCK_PATH=/var/www/backend/tests/Fixtures/jamf/emma-leaves.json docker compose up -d backend
docker compose up -d backend   # back to the real mock file
```

## Where the improved sync service lives

The brief asks for an improved version of the reference `RefJamfSyncService` ([files/RefJamfSyncService.php](files/RefJamfSyncService.php)). Instead of one class that both reads Jamf's data and saves it, the sync is split into three parts:

| Part | File | What it does |
|---|---|---|
| Reader | [JamfProvider.php](backend/app/Mdm/Jamf/JamfProvider.php) | Reads the Jamf mock file and checks the whole list before handing anything over |
| Translator | [JamfDeviceMapper.php](backend/app/Mdm/Jamf/JamfDeviceMapper.php) | The only place that knows Jamf's field names. Turns one Jamf laptop into a standard shape, or skips it with a reason |
| Shared sync engine | [MdmSyncService.php](backend/app/Mdm/MdmSyncService.php) | All the saving logic, for any MDM tool: creates, updates, follows reassignments, all in one transaction |

Why it's split: adding another MDM tool (Intune, Kandji) means writing one reader and one translator. The engine, the database and the website stay the same. A test proves this with a made-up second tool.

### Improvements over the reference

| # | Reference weakness | What we did | Status | Proved by |
|---|---|---|---|---|
| R1 | Reading Jamf and saving were mixed in one class | Reader, translator and shared engine | Fixed | [SyncEngineTest](backend/tests/Feature/Mdm/SyncEngineTest.php): engine works for another provider |
| R2 | A laptop that lost its owner in Jamf kept its old owner | Marked "To be collected from {last owner}", owner link removed | Fixed | SyncEngineTest t5 |
| R3 | Laptops missing from Jamf stayed forever | Flagged "No longer in Jamf · last seen with {last owner}", never deleted; the flag clears if the laptop comes back | Fixed | SyncEngineTest t8, t9, t15, t16 |
| R4 | A phone number removed in Jamf was kept | Removed values are removed here too | Fixed | SyncEngineTest t17 |
| R5 | A new person without a name could crash the save | Name falls back to the email; laptop name to the model | Fixed | SyncEngineTest t18, t19 |
| R6 | Storage always empty; most specs thrown away | Every Jamf field is kept (storage isn't in the mock) | Fixed | [JamfDeviceMapperTest](backend/tests/Unit/Mdm/JamfDeviceMapperTest.php): Alex's MacBook Pro field by field |
| R7 | "Updated" counted laptops that didn't change; no people counts | Only real changes count; people counted too | Fixed | SyncEngineTest t2, t3 |
| R8 | Two syncs at once could collide | Only one sync runs at a time; a second one is refused with "A sync is already running" and recorded | Fixed | [SyncLockTest](backend/tests/Feature/Mdm/SyncLockTest.php) t25 |
| R9 | No tidying of serials and emails; the same serial processed twice | Tidied; conflicting duplicates change nothing and are reported | Fixed | SyncEngineTest t23, t24 |
| R10 | Odd fallback that used the username as an email | Removed; an invalid email skips that entry | Fixed | SyncEngineTest t22 |
| R11 | No record of when syncs happened | Every attempt is stored: when, result, counts, skips, and the reason if it failed | Fixed | [SyncHistoryTest](backend/tests/Feature/Mdm/SyncHistoryTest.php) |
| R12 | No pagination | The completeness check already works across pages; paging itself isn't needed for the one-page mock | Partly | [JamfProviderTest](backend/tests/Feature/Mdm/JamfProviderTest.php): count mismatch stops the sync |
| R13 | Unclear what happens when something breaks halfway | The whole list is checked first; everything is saved in one transaction; one bad entry is skipped with a reason | Fixed | SyncEngineTest t14, t21, crash halfway saves nothing |
| R14 | One database query per laptop | Measured: about 3 queries per laptop; 5,000 laptops sync in about 3 seconds, 12,000 in about 7. Listed as a limitation | Not yet | [TEST-LOG.md](docs/TEST-LOG.md), "Scale" |

Tiers are explained at the top of [docs/DECISIONS.md](docs/DECISIONS.md); what's designed but not built is in the table near the top of this README.

## Project layout

| Folder | What's in it |
|---|---|
| `backend/` | Laravel API (PHP 8.4, Laravel 13.35) |
| `frontend/` | React website (React 19, Vite) |
| `files/` | WorkWize's original files: the Jamf mock response, the reference sync code, the wireframe |
| `docs/` | Principles, decisions, test log (with screenshots in `docs/screenshots/`), mistakes, changes, and ideas and limitations |

## Notes

- Local development setup only. The passwords in `docker-compose.yml` are local-only values, and the website and backend are only reachable from this computer.
- Debug mode is on (`APP_DEBUG=true` in `backend/.env.example`), which suits local development; it should be off anywhere else.
- WorkWize's template link returned a 404, so this repo was created fresh and the template's files were copied in unchanged.
