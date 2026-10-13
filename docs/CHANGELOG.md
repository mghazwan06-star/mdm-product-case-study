# Changelog

What was built, step by step. Newest at the top.

<!-- Format:
## YYYY-MM-DD: Step X.Y short title
- What changed (one or two lines)
- Why
- Commit: <hash>
-->

## 2026-10-13: Step 2.3 Full sync result message
- The message after a sync now starts with "⚠ 1 laptop needs attention" and names each laptop that newly needs a person, with a link to it ("To be collected from Emma Johnson: Emma’s MacBook Air"). Laptops still waiting from an earlier sync aren't repeated. "Show details" reveals every count, zeros included. This replaces the earlier one-line "now needs collecting" message.
- An ⓘ button next to each status that needs a person (in the list, on the laptop page and in the message) explains what it means and what to do.
- Why: things that need a person must be impossible to miss, while quiet days stay one line. [Our addition]
- Commit: (this save point)

## 2026-10-13: Step 2.2 One sync at a time
- Only one sync can run at a time, whether it's started from the website (by one person or two) or the command line. A second one is refused with "A sync is already running. Please wait for it to finish.", shown as a calm grey message, and recorded in the sync history. The lock is always released, also after a failed sync.
- Why: two syncs at once could collide (a weakness in the reference code). [Our addition]
- Commit: 4efdcb0

## 2026-10-13: Step 2.1 Sync history
- Every sync attempt is now recorded: when it ran, whether it succeeded or failed, the counts, the skips, and for a failure the plain message plus the technical reason. A save that fails halfway is undone, but the record of the attempt is kept.
- The very first successful sync now says "✓ First sync complete: 7 laptops and 6 people added."
- Why: lets you see what happened and when, and fixes the reference code's missing sync log. [Our addition]
- Commit: 424480e

## 2026-10-13: Audit fix: README points to the improved sync service
- New README section "Where the improved sync service lives": the three parts (reader, translator, shared engine), why it's split, and a table of every weakness in the reference code with what we did, its status and the test that proves it. Also added: "Try a scenario", a pointer to the test log, a note that debug mode is on for local use, and "press Sync Now".
- Why: the brief grades an improved `JamfSyncService`, and there's no class by that name, so a reviewer needs to be told where to look (found in the Tier 1 audit). [Required by brief]
- Commit: b1bbdf9

## 2026-10-13: Audit fix: documents match the app
- `DECISIONS.md` now has a Status column: "Built", or "Planned (Tier 2/3)" with what happens today. Nothing planned reads as if it works.
- `TEST-LOG.md` now has real content: how to run both suites, which test proves each brief rule, the numbered scenarios, the deliberate-break approach and the manual checks. `PRODUCT-NOTES.md` is removed until it has content. Two missing limitations added (the unused `sync_runs` table; one query per laptop). Private shorthand removed from the logbook.
- Why: the decisions file described unbuilt features as working (found in the Tier 1 audit). [Our addition]
- Commit: e7254a2

## 2026-10-13: Audit fix: plain code comments
- Code comments and test names no longer use our private shorthand (decision codes, tier and step numbers). They say it in plain words or point to a row in `docs/DECISIONS.md`. Fixed an out-of-date comment, and added one line explaining why the sync service keeps state during a run.
- Why: a reviewer can't look up references to our private notes (found in the Tier 1 audit). [Our addition]
- Commit: 7b9d9c5

## 2026-10-13: Audit fix: hardening
- The website and backend ports now only listen on this computer (127.0.0.1), not the local network. Laravel's file serving and upload routes, which this app doesn't use, are switched off. Opening /api/sync in a browser now explains how to run a sync instead of talking about editing.
- New tests: the health check (all OK, and a missing Jamf file), the backend address, the /api/sync message, the switched-off file routes, and `php artisan mdm:sync` failing clearly (unreadable file, unknown provider).
- Why: found in the Tier 1 audit. [Our addition]
- Commit: f468596

## 2026-10-13: Audit fix: starter leftovers removed
- Removed what the Laravel and Vite starters left behind and we don't use: Laravel's welcome page (the backend address now answers with a short JSON note pointing to the website and the health check), both template READMEs, the "true is true" example tests, the seeder that created a "Test User", Laravel's own frontend files and their setup steps, and the Vite logo (now a simple laptop icon).
- Why: leftovers look careless to a reviewer (found in the Tier 1 audit). [Our addition]
- Commit: ba50d68

## 2026-10-13: Audit fix: delete dialog works fully with the keyboard
- Tab and Shift+Tab now stay inside the dialog, and when it closes, focus goes back to the Delete button that opened it (or to the page title if that row was deleted).
- Why: keyboard users could tab into the page behind the dialog and lost their place when it closed (found in the Tier 1 audit). [Our addition]
- Commit: a568584

## 2026-10-13: Audit fix: lists readable on a phone
- On narrow screens each row is now two lines: name and serial on top, the status label (or laptop count) below, Delete on the right. Before, the label covered the laptop name.
- Why: found in the Tier 1 audit at phone width. [Our addition]
- Commit: 02aa90b

## 2026-10-12: Review fix 5: colour contrast checked
- Measured every text and background colour pair against WCAG AA (4.5:1 for normal text). All 16 pass (lowest: 4.56, the close button on the red box), so no colour changed. Added a test that reads the colours from the stylesheet and fails if any pair drops below 4.5:1.
- Why: status labels and messages must be readable for everyone (asked for in the screenshot review). [Our addition]
- Commit: 7b594ff

## 2026-10-12: Review fix 4: laptop page names the last owner
- When nobody has a laptop now, the Owner line says "No current owner · last owner: Emma Johnson" (just "No current owner" if no last owner is known).
- Why: the essentials should say who to talk to without opening "Additional information" (found in the screenshot review). [Our addition]
- Commit: a5806f8

## 2026-10-12: Review fix 3: times in the viewer's local time
- Jamf sends times in UTC; the website now shows them in the viewer's own time zone (e.g. 21:10 in Amsterdam instead of 19:10). The purchase date is a calendar date and stays as it is. Website tests run in a fixed time zone (Amsterdam) so the results don't depend on the computer.
- Why: times were two hours off for a user in Amsterdam (found in the click-through). [Our addition]
- Commit: f0be114

## 2026-10-12: Review fix 2: only the latest message shows
- The sync result and the "… was deleted" note now share one message slot. Starting a new sync or a delete clears the old message, so an out-of-date note can't sit next to a new result.
- Why: after a delete and a sync, two green boxes showed, one of them out of date (found in the click-through). [Our addition]
- Commit: bdf2d51

## 2026-10-12: Review fix 1: sync message says what was counted
- The message now names what it counts, and includes people: "✓ Sync complete. Laptops: 7 new. People: 6 new." People are only mentioned when something changed for them. The attention line still comes first, and a laptop that needs collecting still isn't counted again as updated.
- Why: "7 new" didn't say 7 of what, and new or changed people weren't shown at all (found in the screenshot review). [Our addition]
- Commit: a3cff0c

## 2026-10-12: Fix: Docker settings now reach the website's requests
- Laravel's development server only passes a short list of settings on to web requests; everything else came from `backend/.env`. So the scenario setting from 4.6e was ignored by the website (and the database settings only worked because `.env` held the same values). Our settings are now passed through, with a test.
- Tests now always use the real mock file, whatever scenario the app is running.
- Why: found in the website click-through; the scenario showed "7 unchanged" instead of a laptop to collect. [Our addition]
- Commit: 2f6fd81

## 2026-10-12: Step 4.6e Scenario files for manual checks
- Added two scenario files (copies of the mock with one change: Emma's laptop loses its owner; a cut-off list) and a start-up setting to sync from one of them. The default is still the real mock file, which is never edited. A test checks each scenario still does what its README says.
- Why: lets anyone see "To be collected" and a failed sync in the website without editing WorkWize's file. [Our addition]
- Commit: af9d498

## 2026-10-12: Step 4.6d Employees page and deletes
- Employees page: name, job title, email and laptop count ("2 laptops", "1 laptop", "No laptops"), with delete.
- Delete on the laptops list, the laptop details page and the people list, always after a confirmation in the page. The wording depends on the situation: a laptop Jamf still lists "will come back on the next sync"; one Jamf no longer assigns "won't come back unless Jamf assigns it to someone again"; a person with laptops gets instructions to reassign or remove the laptops in Jamf first (`DECISIONS.md` row 15). Cancel deletes nothing.
- Why: the brief requires a people list and delete for both. [Required by brief] Situation-specific confirmations: [Our addition]
- Commit: db9b8e7

## 2026-10-12: Step 4.6c Laptop details page
- The wireframe's "Asset Details": name and status at the top, then the essentials (owner, model, processor, memory in GB, serial). Everything else sits behind "Additional information", grouped into owner details, hardware, lifecycle (purchase date with age, last check-in) and source (Jamf IDs, last synced).
- Missing values say "Not provided by Jamf". A laptop with no current owner shows its last owner instead.
- Why: the wireframe asks for a details page with the asset's attributes. [Required by brief] Grouping and missing-value wording: [Our addition]
- Commit: a5745f8

## 2026-10-12: Step 4.6b Assets page and Sync Now
- Assets page as in the wireframe: "Asset Management", Sync Now, one row per laptop with its status label ("Assigned to …", "To be collected from …", "No longer in Jamf · last seen with …", "Owner removed in WorkWize (…) · returns on next sync"). Amber for statuses that need a person.
- Sync result message that stays until closed: zeros hidden, skip reasons always shown, failures in plain words. Laptops that now need collecting are shown first and not counted again as "updated". Sync Now is also on Home, and can't be pressed twice while a sync runs.
- Why: the brief requires a list of synced assets and a sync. [Required by brief] Labels and message rules: [Our addition]
- Commit: b67b0be

## 2026-10-12: Step 4.6a Website frame
- Replaced the temporary check page with the real website frame: navigation (Home · Assets · Employees, as in the wireframe), page routing, one place that talks to the API with plain error messages, shared styles, and website tests (Vitest).
- Home shows the totals with links, or "No data yet. Press Sync Now to import laptops from Jamf." Wording always uses real singular/plural ("1 laptop", "6 people"), never "(s)".
- Why: the brief requires a React UI that uses the backend API. [Required by brief]
- Commit: 12a2d91

## 2026-10-12: Step 4.5 API
- Added the doorways the website uses: run a sync, list laptops, laptop details, delete a laptop, list people (with how many laptops each has now), delete a person. No create or edit doorways.
- Deleting a person: their current laptops become "Owner removed in WorkWize" in the same transaction, never "To be collected"; they return to the person on the next sync. Errors come back as plain sentences (unknown record, edit attempts, failed sync).
- Why: the brief requires the frontend to use the backend's API, with delete only. [Required by brief] Person-delete rule and plain errors: [Our addition]
- Commit: 8a4415a

## 2026-10-12: Duplicate serials: conflicts change nothing
- Changed how the same serial listed twice is handled. Before: the first entry won. Now: if the entries conflict, nothing changes for that laptop and every entry is reported ("listed twice with conflicting information"); identical entries are used normally. The serial always counts as seen (needed for "No longer in Jamf" later).
- Why: Jamf contradicting itself can't be trusted either way; the first entry was an arbitrary pick. Refines the earlier duplicate decision. [Our addition]
- Commit: a0d2a32

## 2026-10-12: Step 4.4 Sync engine (brief rules)
- Added the shared sync engine: read → translate → skip duplicates ("listed twice") → apply everything in one transaction. Creates and updates laptops and people, follows reassignments, mirrors blanks, recreates deleted records, and marks a known laptop that loses its owner as "To be collected" (keeping the last owner). Added `php artisan mdm:sync`.
- Counts are honest: our own timestamps never count as a change, and "became to be collected" counts only the moment it happens. MySQL reorders the keys of the specs, so specs are compared ignoring order. Skip wording uses the tool's name ("no owner in Jamf").
- Why: brief rules 1–8. [Required by brief] To be collected, all-or-nothing, listed twice, first-entry rule: [Our addition]
- Commit: 5aa1a3b

## 2026-10-12: Step 4.3 Provider structure and Jamf translator
- Added the MDM provider agreement, a standard device shape, and the Jamf reader and translator. The translator is the only place that knows Jamf's field names; it tidies emails and serials, applies the name fallbacks, turns blanks into empty values and skips broken entries with a reason.
- The reader checks the whole list before handing anything over: a missing or broken file, no results list, or fewer/more devices than `totalCount` stops everything. Nothing is saved yet.
- Why: the brief asks to make future MDM providers easy. [Required by brief] Checks and skip reasons: [Our addition]
- Commit: 44b404e

## 2026-10-12: Step 4.2b Tables for people, laptops and sync history
- Added `employees` (unique email), `assets` (unique serial, status, current owner, last-owner snapshot, specs) and `sync_runs`. Both unique rules ignore upper/lower case. Deleting a person keeps their laptops and only clears the link. Indexes on laptop status and last owner email for the attention lists and badges later.
- Not yet: on deleting a person, their laptops' status must also become "Owner removed in WorkWize" (`DECISIONS.md` row 15). That's app behaviour, planned for the delete step (4.5).
- Why: the brief requires assets and employees in MySQL, unique by serial and email. [Required by brief] Statuses, last owner and sync history columns: [Our addition]
- Commit: 46bfa7c

## 2026-10-12: Step 4.2a Separate test database
- Tests now run in their own MySQL database (`workwize_test`), created automatically when MySQL starts for the first time. A safety check stops the tests if they're ever pointed at another database.
- Why: Docker's database settings would otherwise have sent the tests to the app's real data, and tests wipe their database. [Our addition]
- Commit: af83b19

## 2026-10-12: Step 4.1e Removed AI-assistant files from the Laravel starter
- Removed `backend/CLAUDE.md` and `backend/AGENTS.md`. Laravel 13's starter ships them; they told an AI assistant to install PHP on the Mac with a downloaded script and add an extra Laravel tool. We run PHP in Docker, and they could be mistaken for our own instructions. Also corrected the Laravel version in the README (13.35).
- Why: keep the repo's instructions ours and accurate. [Our addition]
- Commit: 8b5a8bb

## 2026-10-12: Step 4.1d Brief renamed, README added
- Renamed WorkWize's `Readme.md` to `BRIEF.md` (content unchanged, checksum verified) and added our own `README.md` with run instructions.
- Why: the brief asks for run instructions in the README, and our README can't share a name with theirs. [Required by brief]
- Commit: 5e303df

## 2026-10-12: Step 4.1c Docker setup and health check
- `docker compose up` now starts MySQL 8.4, the Laravel backend (PHP 8.4) and the React website. The backend waits for the database, installs its libraries, creates the tables and starts; the website waits for the backend.
- Added `GET /api/health` (backend, database, Jamf file) and a temporary check page. The `files/` folder is shared with the backend read-only. Removed the unused starter images.
- Why: the brief requires a one-command Docker start. [Required by brief]
- Commit: afa3b92

## 2026-10-12: Step 4.1b React + Vite skeleton
- Added the standard React 19 + Vite 8 starter in `frontend/`, unchanged, so our own code is easy to tell apart.
- Why: the brief requires React. [Required by brief]
- Commit: aa283ce

## 2026-10-12: Step 4.1a Laravel skeleton
- Added the standard Laravel starter project (template v13.11.0) in `backend/`, unchanged, so our own code is easy to tell apart. The Laravel framework itself (v13.35.0) is installed by Docker in 4.1c.
- Why: the brief requires Laravel. [Required by brief]
- Commit: 2b3ba28

## 2026-10-12: Setup audit fix: .gitignore
- Removed the `build/` rule. It blocked any folder named "build" anywhere (on a Mac even "Build"), which could hide real code. Built output is still ignored via `dist/` and `public/build/`.
- Why: found in the audit of the project setup. [Our addition]
- Commit: f9ca750

## 2026-10-12: Step 3.7 Decisions summary
- Filled `docs/DECISIONS.md` with a short summary of the product decisions and assumptions.
- Why: the brief asks us to document assumptions. [Required by brief]
- Commit: 9eb6875

## 2026-10-12: Step 3.6 Logbook templates
- Added `docs/` with empty logbook files: changelog, decisions, mistakes, ideas and limitations, test log, product notes.
- Why: keep a short, honest record of the work as it happens. [Our addition]
- Commit: a336c1b

## 2026-10-11: Step 3.5 Template recreated
- Added the brief (`Readme.md`), the `files/` folder (mock Jamf data, reference sync code, wireframe, screenshot) and a `.gitignore`.
- Why: WorkWize's template link returned a 404, so the template was rebuilt from the email attachments. Files copied unchanged (checksums verified). [Required by brief]
- Commit: 3664be3
