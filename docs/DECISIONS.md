# Decisions and assumptions

This file is updated at submission to match what was actually built.

Where the brief is silent or open to interpretation, this is what we chose and why.
Tags: **[Required by brief]** · **[Our addition]**

**Status** says what works today. The work is done in steps: **Tier 1** (everything the brief asks for: built), **Tier 2** (core improvements) and **Tier 3** (polish). Nothing marked "Planned" works yet.

## The idea behind most choices

Jamf decides what is true: who has a laptop and what's in it. WorkWize decides how
to show that and what to do about it. A sync never deletes anything. It creates,
updates or flags. Only a person pressing delete removes data.

## How it's built for more than Jamf

Each MDM tool gets a small translator that turns its data into one standard format.
One shared sync engine does all the rest. Adding Intune or Kandji means writing only
a new translator. **[Required by brief]** · **Status: Built** (see the README, "Where the improved sync service lives")

## Interpretations of the brief (read these first)

| # | Situation | Decision | Why | Tag | Status |
|---|---|---|---|---|---|
| 1 | A laptop already in WorkWize loses its owner in Jamf | Kept and marked **"To be collected from {last owner}"**. The owner link is removed, but the last owner's name and email are remembered | The brief says unassigned devices must not be imported. We read that as applying to *new* laptops. A known laptop that loses its owner is still company property, and this is exactly when it needs collecting. Removing it would hide it at the worst moment | [Our addition] | Built |
| 2 | A laptop in WorkWize is missing from the Jamf list | Kept and marked **"No longer in Jamf, last seen with {last owner}"**. Clears itself if it comes back | The brief doesn't cover this. It could mean retired, lost, stolen, or a Jamf error. Flagging is safe in all four cases; deleting is not | [Our addition] | Built |

## How the sync behaves

| # | Situation | Decision | Why | Tag | Status |
|---|---|---|---|---|---|
| 3 | Same laptop, same person | Matched by serial number and email. Emails are lowercased, serials uppercased, spaces trimmed (and, since testing, removed inside serials too: row 19) | "Alex.Smith@…" and "alex.smith@…" are one person | [Required by brief] + normalisation [Our addition] | Built |
| 4 | New laptop with no owner in Jamf | Not imported; shown as skipped with the reason | Brief rule | [Required by brief] | Built |
| 5 | Something changes in Jamf (RAM, owner, name) | Copied over on the next sync | Jamf is the source of truth | [Required by brief] | Built |
| 6 | Jamf removes a value (e.g. a phone number) | Removed here too, shown as "Not provided by Jamf" | Keeping an old value would show data that was deleted on purpose, maybe for privacy | [Our addition] | Built |
| 7 | A name is missing | A person's name falls back to their email; a laptop's name falls back to its model, then "Unknown device ({serial})" | Screens are never blank | [Our addition] | Built |
| 8 | The Jamf file can't be read (missing, broken), or holds a different number of laptops than its `totalCount` says | Nothing changes, and a clear error is shown. The failed attempt is also recorded | A half-read list must never change data. `totalCount` covers all pages of a real Jamf API, so the check counts after every page is collected. No `totalCount` → no check | [Our addition] | Built |
| 9 | The list is readable but empty or much shorter | Applied. Missing laptops are flagged (row 2), never deleted. The next good sync clears the flags | Real retirements shouldn't be blocked; the worst case is flags, not lost data | [Our addition] | Built |
| 10 | One broken entry (no serial, invalid email) | Only that entry is skipped, with the reason. An invalid email counts as broken, not "no owner" | One bad entry shouldn't block the rest. A typo in Jamf must not send someone to collect a laptop that's still in use | [Our addition] | Built |
| 10a | The same person appears on several laptops with different details in one list (e.g. phone on one entry, blank on another) | Their details come from their **first** entry in the list; later entries in the same sync don't overwrite them | One clear, predictable rule instead of the last entry silently winning | [Our addition] | Built |
| 10b | Jamf lists the same laptop (serial) twice | If the entries conflict, Jamf is contradicting itself, so nothing changes for that laptop and both entries are reported ("listed twice with conflicting information"). Identical entries are harmless and used normally. Either way the laptop counts as seen | Picking one of two conflicting entries is a guess and could assign a laptop to the wrong person | [Our addition] | Built |
| 11 | Sync pressed twice | The second one is refused: "A sync is already running" | Two syncs at once could collide | [Our addition] | Built (also when two people press it at once, or the command line runs at the same time) |
| 12 | Record of syncs | Every sync is stored: when, result, counts, skips | Lets you see what happened and when | [Our addition] | Built (also failed and refused attempts). A page to view the history: Planned (Tier 3) |
| 13 | Laptop hasn't checked in with Jamf for a long time | Warning only, after 30 days (Jamf's own guidance). Measured against the newest check-in in the same list, not today | The mock data is from January 2026; comparing to today would flag every laptop | [Our addition] | Planned (Tier 3). The last check-in date is already shown on the laptop page |

## Deleting

| # | Situation | Decision | Why | Tag | Status |
|---|---|---|---|---|---|
| 14 | Delete a laptop or person | Removed from WorkWize only (really removed, not hidden). Comes back on the next sync if Jamf still lists it | Brief rule. A real delete avoids problems when the sync recreates the record | [Required by brief] | Built |
| 15 | Delete a person who still has laptops | Allowed, after a warning that says they'll come back and how to remove them for good (in Jamf). Their laptops stay, marked "Owner removed in WorkWize" | Blocking it would break the brief's "re-sync recreates deleted employees", since every person in the mock data has a laptop | [Our addition] | Built |
| 16 | Delete a "To be collected" or "No longer in Jamf" laptop | Removed from WorkWize. Won't come back unless Jamf assigns it to someone again | Jamf no longer assigns it, so the sync has nothing to recreate. Usually this means "collected or retired, done" | [Our addition] | Built |

## Screens

| What | Tag | Status |
|---|---|---|
| Assets list, laptop details page, people list, delete with a confirmation for both (as in the wireframe) | [Required by brief] | Built |
| After a sync, a message stays at the top until closed, and only the latest message shows. Zero counts are hidden; skipped laptops always show the reason | [Our addition] | Built |
| Laptops that newly need a person come first in that message, by name and with a link ("⚠ 1 laptop needs attention · To be collected from Emma Johnson: Emma’s MacBook Air"). Laptops still waiting from an earlier sync aren't repeated: the message is the news, the labels in the list are the record | [Our addition] | Built |
| "Show details" in the message reveals every count, zeros included | [Our addition] | Built |
| A "Needs attention (n)" button on the laptops list that filters it to the laptops that need a person, stays after the message is closed, and disappears when there's nothing to do | [Our addition] | Built |
| The same button on the people list | [Our addition] | Planned (Tier 3, with the people badges) |
| Laptop page: the essentials first, everything else under "Additional information"; missing values say "Not provided by Jamf" | [Our addition] | Built |
| People: badges for laptops to collect, and a page per person | [Our addition] | Planned (Tier 3) |
| Home: an overview with the items that need attention and the last sync time (today Home shows the totals and Sync Now) | [Our addition] | Planned (Tier 3) |
| An ⓘ button next to each status that needs a person (in the list, on the laptop page and in the message), explaining what it means and what to do | [Our addition] | Built |
| An ⓘ button next to every other field | [Our addition] | Planned (Tier 3) |

## Decisions after testing

Testing (see [TEST-LOG.md](TEST-LOG.md), "Findings") turned up rare data and failure cases. Muhammad decided each one.

| # | Situation | Decision | Why | Tag | Status |
|---|---|---|---|---|---|
| 17 | A name, job title or other text over 255 characters stopped every sync (F1) | Stored in full; nothing is cut. A serial or email that's too long skips only that laptop, with a reason | Jamf decides what's true; one rare entry must not stop everyone | [Our addition] | Built |
| 18 | Jamf sends a value WorkWize can't use: a number written as text, a date in an ambiguous order, an impossible value like a battery of 140% (F3, F7, F8) | Numbers written as text are read as numbers. Dates must be year-first (2026-01-05, with or without a time); a missing time zone means UTC. Anything else (like 05/01/2026, or 140%) is shown as **"Jamf sent a value WorkWize couldn't read"** with what Jamf sent, never guessed and never hidden. "Not provided by Jamf" stays for values Jamf really didn't send | The data must stay honest; never guess the order of a date | [Our addition] | Planned |
| 19 | A serial with a space inside (F5) | Spaces inside serials are removed, like the existing tidy-up (row 3) | One laptop is one record | [Our addition] | Built |
| 20 | Accented email addresses (F6) | Accepted. "josé@…" and "jose@…" are two different people | Real people have these addresses | [Our addition] | Planned |
| 21 | A laptop count written as text ("7"), or an invisible character some Windows tools put at the start of a file (F4, F9) | Accepted; the list is read normally | A harmless format difference must not stop every sync | [Our addition] | Built |
| 22 | A sync crashed (backend or database restarted, out of memory) (F10) | The next Sync Now says honestly: "The last sync was interrupted. Nothing was changed. You can sync again in about {n} minutes." The history marks that sync "interrupted" | Never say something untrue; the app must not look stuck | [Our addition] | Planned |
| 23 | Technical errors reaching the screen; a wrong "couldn't reach" message; endless "Loading…" (F2, F11, F12, F13) | Plain messages only (details go to the server log); "Couldn't reach WorkWize" only when nothing answers; after 15 seconds of waiting, a line saying it's taking longer than usual | Give context and instructions, not raw errors; a status should lead to action | [Our addition] | Planned |
| 24 | Very large customers (over about 12,000 laptops) and very long sync messages (F14, F15) | Limitations, with the measured numbers (see [IDEAS-AND-LIMITATIONS.md](IDEAS-AND-LIMITATIONS.md)) | Far above a mid-size customer; the fix belongs with a live Jamf connection | [Our addition] | Accepted as limitations |

## Other assumptions

- WorkWize can't know whether someone left the company, so people are never removed automatically.
- Jamf data is read-only here: no editing (brief), and nothing is written back to Jamf.
- A person listed on several laptops with different details: the first entry in the list is used for that sync.
- Storage isn't in the mock data, so it isn't shown.
- One company, no login.
- A Jamf date with a time but no time zone is read as UTC (Jamf's own API always sends one).
- The data comes from the provided mock file, not a live Jamf connection (so no pagination or login to Jamf).
- WorkWize's template link returned a 404, so this repo was created fresh and their files were copied in unchanged.
- WorkWize's brief is kept unchanged as `BRIEF.md`; `README.md` holds our own run instructions. **[Our addition]**
