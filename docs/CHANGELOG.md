# Changelog

What was built, step by step. Newest at the top.

<!-- Format:
## YYYY-MM-DD: Step X.Y short title
- What changed (one or two lines)
- Why
- Commit: <hash>
-->

## 2026-10-16: Credit and counts made consistent (Muhammad's decisions)
- HOW-WE-BUILT-THIS: 4 changes of mind (the people-page badges added), and one sentence that the documentation step added a few more catches, kept in the private build journal. The sync history page is "Quick approval 5, extended by Muhammad with the history page and PDF" in WHAT-I-WOULD-DO-NEXT and DECISION-RECORDS. MISTAKES says "Claude Code" instead of "I"; the entries are unchanged.
- Why: every doc should give the same credit to the same side. [Our addition]
- Commit: (this save point)

## 2026-10-16: Numbers checked across the docs
- HOW-WE-BUILT-THIS: 83 save points after this documentation step (73 before it); catch and mistake counts marked as "build and testing"; heading "Mistakes and changes of mind". Test totals (133 backend, 101 website) and deliberate breaks (71) already matched in every doc.
- Why: the same number must mean the same thing everywhere. [Our addition]
- Commit: (this save point)

## 2026-10-16: README: a docs index and a new 10-minute path
- A table of every document (what it shows, who it's most useful for) near the top, and a "start here" path through the README, DECISION-RECORDS, HOW-WE-BUILT-THIS, TEST-LOG and WHAT-I-WOULD-DO-NEXT, with minutes for each.
- Why: with eleven documents, a reviewer needs to know where to start and what to skip. [Our addition]
- Commit: (this save point)

## 2026-10-16: The same "designed, not built" wording everywhere
- PRINCIPLES and TEST-LOG said "planned" for Tier 3 features; they now say "designed, not built (time)", like the rest of the docs.
- Why: one honest phrase for what isn't built, so nobody reads "planned" as "coming soon". [Our addition]
- Commit: (this save point)

## 2026-10-16: Small wording fixes from the docs audit
- MISTAKES no longer refers to a private step number ("step 4.2"); a picture caption in PRODUCT-NOTES says plainly what the two pictures show.
- Why: a reviewer can't look up our private step numbers. [Our addition]
- Commit: (this save point)

## 2026-10-16: README links instead of repeating
- The "designed, not built" table in the README is replaced by the tiers diagram and a link to the same table in WHAT-I-WOULD-DO-NEXT (DECISIONS keeps it as the record of status). "Three product calls" links to DECISION-RECORDS for the full reasoning.
- Why: one table in three places drifts out of step. [Our addition]
- Commit: (this save point)

## 2026-10-16: Same decision numbers everywhere
- README, DECISIONS and PRINCIPLES now say "Decision 6.1" to "6.4" (and 6.2b, 6.2c) like DECISION-RECORDS, instead of "Decision 6 (the … page)", and link to DECISION-RECORDS for the options and reasons.
- Why: a reviewer should be able to look up every decision a doc mentions. [Our addition]
- Commit: (this save point)

## 2026-10-16: Pictures next to the decisions they prove
- Before/after pairs for the leaver (decision 1) and deleting a person (decision 3); the broken-list and empty-list messages under decision 4; the laptop page with "Not provided by Jamf"; "Not provided by Jamf" next to "couldn't read" in PRODUCT-NOTES; the long-name catch in HOW-WE-BUILT-THIS. Five new files, all on WorkWize's mock data, reused from the earlier click-through after checking the screens haven't changed since (the broken-list picture is cropped to the message, because the list's labels gained an ⓘ later).
- Why: a picture of the screen proves the decision faster than a description. [Our addition]
- Commit: (this save point)

## 2026-10-16: Six diagrams
- How a sync flows (README, PRODUCT-NOTES); the four laptop statuses and the sync steps with "all or nothing" (DECISION-RECORDS); the build loop (HOW-WE-BUILT-THIS); phases and tiers, built vs designed (README, WHAT-I-WOULD-DO-NEXT); one principle traced to code and tests (PRINCIPLES). Drawn with Mermaid, which GitHub shows as pictures; each one checked with the Mermaid library before saving.
- Why: a picture of the flow and the statuses is quicker to read than the tables. [Our addition]
- Commit: (this save point)

## 2026-10-16: Every accepted trade-off in the limitations list
- Nine rows added to "Known limitations": "To be collected" is only a label, new unassigned laptops are never imported, retired laptops stay flagged, deleted records come back, a Jamf glitch can blank a field, no storage, the stale check's design, a delete keeps no trace, and labels that say "Jamf" as fixed text. One line above the table says each DECISION-RECORDS trade-off has a row.
- Why: the "limitations list" links in DECISION-RECORDS must land on a real row. [Our addition]
- Commit: (this save point)

## 2026-10-16: Four documents that show the thinking
- Added HOW-WE-BUILT-THIS, DECISION-RECORDS, PRODUCT-NOTES and WHAT-I-WOULD-DO-NEXT (written by Muhammad with claude.ai, fact-checked against the code and logs). Three wording fixes from the check: the status labels say "Jamf" as fixed text today; the save-point count is "before these documents"; the ⓘ row also mentions "couldn't read".
- Why: a reviewer can see the options, reasons and changes of mind, not only the rules. [Our addition]
- Commit: (this save point)

## 2026-10-15: README made easier for reviewers
- A "start here" reading path, three product calls that show the thinking (the leaver, the vanished laptop, odd Jamf data), private shorthand ("Quick approval Q5") replaced with plain names, and the `docs/` line updated.
- Why: a reviewer should find the thinking in minutes. [Our addition]
- Commit: (this save point)

## 2026-10-15: Test log counts
- The deliberate-break count now includes both F16 checks (the style test and the browser measurement): 71.
- Commit: 8c9396e

## 2026-10-15: Screenshots in the repo
- 16 small screenshots in `docs/screenshots/` (about 30 KB each): before and after for F2/F12, F3/F7/F8, F10, F11, F13 and F16, plus emoji and right-to-left names, a name that looks like web code, 5,000 laptops, the empty-list message and "To be collected". Shown next to their cases in `TEST-LOG.md`, and a few in the README. The old note that pictures were kept elsewhere is gone.
- Every picture was checked first: only fake mock or generated data. One older picture used Muhammad's own name in Arabic as test data; it was retaken with a made-up name, and the same name was replaced in the odd-data test and its TEST-LOG row.
- Why: the edge cases are easier to judge when you can see them. [Our addition]
- Commit: 2a63ecb

## 2026-10-15: Designed, not built: Tier 3 and Tier 4
- README and `DECISIONS.md` now say plainly that Tier 1 and Tier 2 are built and tested, and that Tier 3 (person page and badges, Home attention list, the people-list filter, sync history page, 30-day check-in warning, ⓘ on every field) and Tier 4 (PDF) were designed but not built because of time. One table: feature, where it was decided, its principles, status "designed, not built (time)". `TEST-LOG.md` says why those scenarios say "not built yet". Nothing new was built.
- Why: a reviewer should see exactly what exists and what is only designed. [Our addition]
- Commit: da654b8

## 2026-10-15: Decisions after testing complete in DECISIONS.md
- Added row 25 (F16, option A) and a note on Muhammad's two refinements: long text stored in full, and "never guess the order" for dates.
- Why: every fix traces back to a decision. [Our addition]
- Commit: 24d2e3d

## 2026-10-15: Long status labels wrap (F16)
- A status label with a very long name now wraps inside its row instead of running off the page and covering other laptops' names. Normal labels still sit on one line. The ⓘ explanation next to "Jamf sent a value WorkWize couldn't read" now has the same style as every other ⓘ text.
- Why: Muhammad chose option A; what the website shows must match the data, with nothing hidden. [Our addition]
- Commit: 3959b16

## 2026-10-15: Final testing audit
- A fresh copy in its own Docker project: both suites pass (backend 133, website 99), and every finding's original case was re-run live, including a real out-of-memory crash and a stopped database. Results in `TEST-LOG.md`, "Final audit".
- Fixed while checking the docs against the app: an older test name that still said odd values "become empty" (renamed; it now also checks they're kept as sent); the README's R14 line now has the measured numbers; the odd-data, scale and resilience sections say their results are as found on 13 Oct.
- New finding F16: a very long owner name makes the status label run off the page and cover other laptops' names. Not changed; waiting for Muhammad's decision.
- Why: prove the fixes in the real app, not only in tests. [Our addition]
- Commit: e55b7f9

## 2026-10-15: Test log summary
- A one-minute summary at the top of `TEST-LOG.md`: what was tested, what was found, what was fixed, what stays a limitation and why. Test counts updated (backend 133, website 99, 68 deliberate breaks).
- Why: a reviewer should get the whole picture before the detail. [Our addition]
- Commit: 7c994cd

## 2026-10-15: Traceability: principle → decision → code → test
- A table at the end of `PRINCIPLES.md` connects every principle to its decisions, the code where it becomes a rule, and the tests that prove it. Honest notes where a principle is only partly built (P15 ⓘ on every field, P18 Home attention list) or has no code of its own (P12, a rule about how decisions were made). Every test named was checked against the code by a script.
- Why: a principle only counts if it shows up in the product. [Our addition]
- Commit: 96452f4

## 2026-10-15: Principles named in the code
- Short comments such as "(P8)" where a principle became a rule in the code: all or nothing, never deleting in a sync, "To be collected" only from a Jamf change, never-blank names, no editing, plain messages, and on the website the attention list, ⓘ, "Show details" and direct links. Comments only; no behaviour changed (backend 133 and website 99 tests pass).
- Why: a reader of the code can see which rule a line protects. [Our addition]
- Commit: 062971d

## 2026-10-15: Test log names the principles
- Every finding names the principle it broke, and every fix the principle that guided it, linked to `PRINCIPLES.md` (for example, the fix that keeps "jose" and "josé" apart: P3 and P4). The odd-data cases too.
- Also fixed: findings F13, F14 and F15 had been put inside the resilience table instead of the Findings table (since the 5.4 and 5.5 save points), the same mistake already corrected once for F10 and F11.
- Why: the link between what broke and why it matters should be visible. [Our addition]
- Commit: 096f8e8

## 2026-10-15: Every decision names its principles
- `DECISIONS.md` has a new "Principles" column: each decision and each screen rule links to the principles behind it in `PRINCIPLES.md`.
- Why: shows that the decisions follow one set of rules, and which ones. [Our addition]
- Commit: e2e5e29

## 2026-10-15: README: how this was designed
- A short section near the top of the README: decisions before code, the principles, how AI was used, and where the testing and mistakes are recorded.
- Why: a reviewer should see the method in one minute. [Our addition]
- Commit: 2e53056

## 2026-10-15: The principles behind the decisions (PRINCIPLES.md)
- New `docs/PRINCIPLES.md`: the 18 principles (P1–P18) every decision follows, with Muhammad's words as he said them, where each came from, and where it's applied.
- Why: WorkWize wants to see how the thinking was done, not only the result. [Our addition]
- Commit: e21808f

## 2026-10-15: Limitations with measured numbers (F14, F15, paging)
- `IDEAS-AND-LIMITATIONS.md` now records Muhammad's decision to keep F14 (very long sync messages) and F15 (the memory ceiling at about 12,000 laptops) as limitations, with the measured numbers, and the measured cost of not paging the lists.
- Why: a limitation is only acceptable if it's written down with evidence. [Our addition]
- Commit: 18fe5e2

## 2026-10-15: "Taking longer than usual" after 15 seconds (F13)
- If a page or a sync waits more than 15 seconds, a second line appears: "This is taking longer than usual. Still waiting for WorkWize to answer." Before, "Loading…" and "Syncing…" could wait forever with no hint.
- Pressing Sync Now hides an old error on the page until the sync finishes, so the page never says "syncing" and "can't reach" at the same time.
- Why: a status should lead to action. [Our addition]
- Commit: f1a4223

## 2026-10-15: Right message when WorkWize answers with an error (F11)
- When WorkWize answers with an error but no explanation (e.g. it crashed), the website says "Something went wrong in WorkWize. Try again in a minute." (for a sync: "…during the sync. Nothing was changed…"). "Couldn't reach WorkWize" is now only used when nothing answers at all. As a second safety net, a technical error page is never shown even if it carries a message.
- Why: messages must be true; "check that the app is running" sent people to check the wrong thing. [Our addition]
- Commit: 06f1e93

## 2026-10-15: Honest message after an interrupted sync (F10)
- If a sync is stopped by a crash (backend or database restart, out of memory), Sync Now no longer says "A sync is already running" for 5 minutes. It says "The last sync was interrupted. Nothing was changed. You can sync again in about {n} minutes." A sync is treated as really running only if it started under a minute ago (normal syncs take seconds).
- The history marks such a sync "interrupted" instead of "running" forever. The lock still expires after 5 minutes.
- Why: never say something untrue, and tell people when they can try again. [Our addition]
- Commit: 6de479a

## 2026-10-15: "Couldn't read" label (F3, F7, F8)
- Numbers Jamf sends as text ("16384") are read as numbers. Dates are read only when written year first (2026-01-05, with or without a time; no time zone means UTC). Anything else, like `05/01/2026`, a battery of 140% or negative memory, is no longer shown as "Not provided by Jamf": the laptop page says "Jamf sent a value WorkWize couldn't read" with what Jamf sent and an ⓘ explanation. Values Jamf really didn't send still say "Not provided by Jamf".
- Checked first: all 21 dates in WorkWize's real mock read exactly as before, and a test now guards that.
- Why: the data must stay honest; never guess and never hide what Jamf sent. [Our addition]
- Commit: 62885a1

## 2026-10-15: Accented email addresses accepted (F6)
- International addresses with accents before or after the @ (`josé@…`, `…@exämple.com`) are accepted; capitals are still ignored (`JOSÉ@…` is `josé@…`).
- The database now compares emails with their accents. Before, it treated `jose@…` and `josé@…` as the same, so accepting accented addresses alone would have merged two different people. It still ignores capitals, so it keeps refusing "Alex.Smith@…" next to "alex.smith@…".
- Why: real people have these addresses, and two people must never be merged. [Our addition]
- Commit: 7842648

## 2026-10-15: Spaces inside serials removed (F5)
- A serial written with a space (`C02 XK1`) now matches the same laptop as `C02XK1`. Before, it created a second record and falsely marked the real laptop "No longer in Jamf".
- Note: a real customer with laptops already stored with a space would need a one-time tidy-up of those records. None exist in this app's data.
- Why: one laptop is one record, and no false alarms. [Our addition]
- Commit: 1de03ac

## 2026-10-15: Invisible marker at the start ignored (F9)
- A Jamf file that starts with the invisible "byte order mark" some Windows tools add is read normally instead of being refused as broken.
- Why: a harmless format difference must not stop every sync. [Our addition]
- Commit: 5ce2217

## 2026-10-15: Count written as text accepted (F4)
- A Jamf list whose laptop count is written as text (`"7"`) is read normally. A count that isn't a number (`"seven"`) is still refused, and a count that doesn't match the list is still treated as an incomplete list.
- Why: a harmless format difference must not stop every sync. [Our addition]
- Commit: af03c77

## 2026-10-15: Plain error messages only (F2, F12)
- When the database is unreachable, Sync Now says "WorkWize's database isn't reachable right now. Nothing was changed. Try again in a minute." and the pages say the same without "Nothing was changed". Any other unexpected error gives "Something went wrong…". No database address, SQL or technical trace reaches the website anymore, even with debug mode on; the details stay in the server log.
- A failed save no longer sends the database's own error to the website; it's kept in the sync history, and the `mdm:sync` command line still prints it for developers.
- Why: give context and instructions, not raw errors (and don't expose internals). [Our addition]
- Commit: 03daa36

## 2026-10-15: Store long text in full (F1)
- Laptop names, people's names, job titles, phone numbers, usernames, Jamf IDs and UDIDs are now stored in full, however long. A serial over 255 characters skips only that laptop, with the reason "serial number too long". Before, one over-long value stopped every sync.
- Two older tests used an over-long laptop name to force a crash halfway through saving; that name is now allowed, so they force the crash with an over-long serial instead (same check: nothing is saved).
- Why: Jamf decides what's true, and one rare entry must not stop everyone. [Our addition]
- Commit: b93073e

## 2026-10-15: Decisions on the testing findings
- Recorded Muhammad's decision for every finding (F1–F15) in `DECISIONS.md` (rows 17–24) and in the findings table of `TEST-LOG.md`. Fixes follow, one save point each.
- Why: every fix should trace back to a decision. [Our addition]
- Commit: e2f6475

## 2026-10-13: Testing 5.5 Findings sorted
- Sorted all 15 findings by our rule: 7 fix proposals (F1, F2 with F12, F4, F9, F10, F11, F13), each with the test that would prove it and a deliberate break; 4 product questions (F3, F5, F6, F7) with options and a recommendation; 3 limitations (F8, F14, F15). Added F15 (the memory ceiling at about 12,000 laptops) to the findings table.
- `IDEAS-AND-LIMITATIONS.md` now has the measured numbers for "one query per laptop" and "no paging", and the new limitations and ideas.
- Why: one short list of what to fix, what to decide and what to accept. Nothing is fixed until approved. [Our addition]
- Commit: 5ac9fd1

## 2026-10-13: Testing 5.4 Resilience
- Restarted the database and the backend in the middle of a sync, stopped the database before Sync Now, froze the backend for 45 seconds during a page load and during a sync, and stopped it while a page loaded. Data was never half-saved, and the app recovered by itself every time (after up to 5 minutes).
- Three new findings: raw database errors reach the screen (F12), "Loading…" and "Syncing…" wait forever without a hint (F13), and the sync message lists every laptop with no limit (F14, 6,871 lines after a big change). The stuck "running" row and lock (F10) happened after every restart too. Nothing changed yet.
- Also moved F10 and F11 into the Findings table; the 5.3 save point had put them in the odd-data table by mistake.
- Why: what a customer is told during an outage matters as much as whether the data survives. [Our addition]
- Commit: 45deabd

## 2026-10-13: Testing 5.3 Scale
- Synced fake Jamf lists of 1,000 to 25,000 laptops in a separate Docker project, through the website's own route. 5,000 laptops sync in about 3 seconds with about 3 database queries per laptop; the laptops page shows all 5,000 in about a second but is about 380 screens long. The sync breaks at about 12,000–13,000 laptops on the 128 MB memory limit, long before the 30-second time limit.
- Two new findings: a crashed sync leaves its history row "running" and blocks Sync Now for 5 minutes with "A sync is already running" (F10), and the website then says "Couldn't reach WorkWize" although it's running (F11). Nothing changed yet. A crash halfway through saving saved nothing, as designed.
- Added the list generator (`backend/tests/Scale/make-jamf-list.php`) so anyone can repeat the test.
- Why: evidence for the "one query per laptop" and "no paging" limitations, and the real breaking point. [Our addition]
- Commit: 523e589

## 2026-10-13: Testing 5.2 Odd data
- Put 19 kinds of odd but realistic Jamf data through the real sync engine (over-long text, numbers as text, a laptop count as text, a serial with a space, international emails, missing sections, emoji, right-to-left text, web code in a name, absurd numbers, other date formats, an invisible character at the start of the file, and more). Each one is in `TEST-LOG.md` with our prediction, what actually happened, and whether the prediction was right.
- The cases that behave correctly are now permanent tests (`OddDataTest`, plus a website test that a name looking like web code is shown as plain text). Nine findings are listed (F1–F9: four fix proposals or to decide, four product questions, one limitation); nothing has been changed yet.
- Why: real exports are messier than the 7-laptop mock. [Our addition]
- Commit: bf6ac94

## 2026-10-13: Test log: business meaning and screenshots
- Every scenario in `TEST-LOG.md` now starts with what it means for the business (e.g. T5 "Emma leaves the company; IT removes her as the laptop's owner") and names the screenshot that proves each manual check, or says that none exists. Findings will name the design principle they break, in plain words.
- Why: a reviewer should see why each test matters, not only what it does. [Our addition]
- Commit: 156ed6a

## 2026-10-13: Testing 5.1 Every agreed scenario
- Ran all 32 agreed scenarios (T1–T32) and wrote the results into `TEST-LOG.md`: scenario, expected, actual, result, where. 27 pass in full; 4 pass for the part that's built (people badges, Home attention list, history page and ⓘ on fields are planned); 1 is not built yet (the 30-day check-in warning). Website click-through for Home, the people list and the empty-list message.
- Why: one table a reviewer can check from top to bottom. [Our addition]
- Commit: 0ea2d78

## 2026-10-13: Empty-list message
- When Jamf sends an empty list, the message now says so: "✓ Sync complete. Jamf's list was empty, so every laptop is now marked 'No longer in Jamf'. Nothing was deleted." Before, the summary was only "✓ Sync complete."
- Why: the most alarming situation deserves the clearest sentence (noticed in the Tier 2 click-through). [Our addition]
- Commit: 682e3d0

## 2026-10-13: Step 2.6 Tier 2 wrap-up
- Two more scenario files for manual checks: `eventful.json` (one laptop to collect, one no longer in Jamf) and `empty.json` (an empty list), each with a test. The test log lists the Tier 2 click-through.
- Why: lets anyone see every Tier 2 situation in the website without editing WorkWize's file. [Our addition]
- Commit: 208052e

## 2026-10-13: Step 2.5 "Needs attention" button
- The laptops page shows a "Needs attention (n)" button whenever laptops need a person (to be collected, no longer in Jamf, owner removed). It stays after the sync message is closed, filters the list to just those laptops ("Show all laptops" goes back), updates after every sync and delete, and disappears at 0.
- Why: the message is the news; the button holds what's still waiting. [Our addition]
- Commit: 1667bfa

## 2026-10-13: Step 2.4 "No longer in Jamf"
- A laptop that's missing from a successfully read Jamf list is now marked "No longer in Jamf · last seen with {last owner}" and listed in the sync message. It's never deleted, its owner link is removed and the last owner is remembered. If it comes back, it follows the list again. An empty list flags every laptop and deletes nothing; the next good sync clears the flags.
- Laptops listed with a broken entry still count as seen, and only laptops from the same tool are checked.
- Why: a laptop that vanished from Jamf could be retired, lost, stolen or a Jamf error; flagging is safe in all four cases (a weakness in the reference code). [Our addition]
- Commit: 01465e8

## 2026-10-13: Step 2.3 Full sync result message
- The message after a sync now starts with "⚠ 1 laptop needs attention" and names each laptop that newly needs a person, with a link to it ("To be collected from Emma Johnson: Emma’s MacBook Air"). Laptops still waiting from an earlier sync aren't repeated. "Show details" reveals every count, zeros included. This replaces the earlier one-line "now needs collecting" message.
- An ⓘ button next to each status that needs a person (in the list, on the laptop page and in the message) explains what it means and what to do.
- Why: things that need a person must be impossible to miss, while quiet days stay one line. [Our addition]
- Commit: c55dc5a

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
