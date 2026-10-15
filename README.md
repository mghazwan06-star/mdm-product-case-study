# WorkWize MDM Device Sync

A Laravel + React app that imports assigned laptops and their owners from Jamf (a mock API response) into MySQL, and shows them in a website.

- The assignment brief from WorkWize: [BRIEF.md](BRIEF.md)
- Our decisions and assumptions, with what's built and what's planned: [docs/DECISIONS.md](docs/DECISIONS.md)

## How this was designed

- **Decisions first, code second.** Every product decision (what happens when someone leaves, when a laptop disappears from Jamf, when the Jamf list is broken, what the screens show) was made by Muhammad Ghazwan before any code was written. Muhammad is the product owner and doesn't have a coding background.
- **18 principles behind the decisions**, in his own words: [docs/PRINCIPLES.md](docs/PRINCIPLES.md). The main one: *Jamf decides what's true; WorkWize decides how to show it and what to do about it* (P1). Decisions name their principles, and so does the code where a principle becomes a rule.
- **AI was used openly.** claude.ai helped think through the options and reviewed every plan; Claude Code wrote the code and the tests. Every plan was approved by Muhammad before it was built, and every save point (commit) names Claude as co-author.
- **Tested and corrected.** The test log ([docs/TEST-LOG.md](docs/TEST-LOG.md)) covers the agreed scenarios, odd data, 1,000 to 25,000 laptops and crashes. What testing found, what Muhammad decided and what was fixed are all in the log. Mistakes, by every side, are in [docs/MISTAKES.md](docs/MISTAKES.md).

## What's built, and what's designed but not built

Tier 1 (the brief) and Tier 2 (core improvements) are built and tested. Tier 3 (polish) and Tier 4 (stretch) were planned and fully designed, but not built because of time. Each has its decision, Muhammad's reasoning and its principles written down in [docs/DECISIONS.md](docs/DECISIONS.md), so it can be built as designed.

| Feature | Decided in | Principles | Status |
|---|---|---|---|
| Person page, with badges for laptops to collect or no longer in Jamf | Decision 6 (the employees page); [DECISIONS.md](docs/DECISIONS.md), Screens | [P5](docs/PRINCIPLES.md#p5-a-status-should-lead-to-action), [P17](docs/PRINCIPLES.md#p17-the-context-ladder-never-leave-something-blank-if-context-exists), [P18](docs/PRINCIPLES.md#p18-take-people-straight-to-the-problem-dont-make-them-search) | Designed, not built (time) |
| Home: the list of what needs attention, and the last sync time | Decision 6 (the home page); [DECISIONS.md](docs/DECISIONS.md), Screens | [P5](docs/PRINCIPLES.md#p5-a-status-should-lead-to-action), [P18](docs/PRINCIPLES.md#p18-take-people-straight-to-the-problem-dont-make-them-search) | Designed, not built (time) |
| "Needs attention" button and filter on the people list | Decision 6 (the sync message); [DECISIONS.md](docs/DECISIONS.md), Screens | [P14](docs/PRINCIPLES.md#p14-things-clear-themselves-when-theyre-done), [P18](docs/PRINCIPLES.md#p18-take-people-straight-to-the-problem-dont-make-them-search) | Designed, not built (time) |
| Sync history page (the history itself is recorded today) | Quick approval Q5; [DECISIONS.md](docs/DECISIONS.md) row 12 | [P4](docs/PRINCIPLES.md#p4-the-data-must-stay-honest-what-the-website-shows-must-match-reality) | Designed, not built (time) |
| "Hasn't checked in with Jamf for {n} days" warning (30 days, measured against the newest check-in in the list) | Decision 6 (the laptop page); [DECISIONS.md](docs/DECISIONS.md) row 13 | [P4](docs/PRINCIPLES.md#p4-the-data-must-stay-honest-what-the-website-shows-must-match-reality), [P5](docs/PRINCIPLES.md#p5-a-status-should-lead-to-action) | Designed, not built (time) |
| ⓘ on every field (today: on statuses that need a person and on "couldn't read") | Decision 6 (the laptop page); [DECISIONS.md](docs/DECISIONS.md), Screens | [P15](docs/PRINCIPLES.md#p15-context-everywhere-on-demand-the-info-symbol), [P17](docs/PRINCIPLES.md#p17-the-context-ladder-never-leave-something-blank-if-context-exists) | Designed, not built (time) |
| **Tier 4:** a PDF of any sync from the history page | Q5 extension (Muhammad's addition); scoped as a stretch goal | [P2](docs/PRINCIPLES.md#p2-design-for-the-everyday-customer-make-the-worst-case-safe-and-fixable-dont-complicate-the-everyday-experience-for-rare-cases) | Designed, not built (time) |

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
| `docs/` | Decisions, test log, changes, mistakes, and ideas and limitations |

## Notes

- Local development setup only. The passwords in `docker-compose.yml` are local-only values, and the website and backend are only reachable from this computer.
- Debug mode is on (`APP_DEBUG=true` in `backend/.env.example`), which suits local development; it should be off anywhere else.
- WorkWize's template link returned a 404, so this repo was created fresh and the template's files were copied in unchanged.
