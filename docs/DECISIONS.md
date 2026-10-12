# Decisions and assumptions

This file is updated at submission to match what was actually built. Anything planned but not built is listed in IDEAS-AND-LIMITATIONS.md.

Where the brief is silent or open to interpretation, this is what we chose and why.
Tags: **[Required by brief]** · **[Our addition]**

## The idea behind most choices

Jamf decides what is true: who has a laptop and what's in it. WorkWize decides how
to show that and what to do about it. A sync never deletes anything. It creates,
updates or flags. Only a person pressing delete removes data.

## How it's built for more than Jamf

Each MDM tool gets a small translator that turns its data into one standard format.
One shared sync engine does all the rest. Adding Intune or Kandji means writing only
a new translator. **[Required by brief]**

## Interpretations of the brief (read these first)

| # | Situation | Decision | Why | Tag |
|---|---|---|---|---|
| 1 | A laptop already in WorkWize loses its owner in Jamf | Kept and marked **"To be collected from {last owner}"**. The owner link is removed, but the last owner's name and email are remembered | The brief says unassigned devices must not be imported. We read that as applying to *new* laptops. A known laptop that loses its owner is still company property, and this is exactly when it needs collecting. Removing it would hide it at the worst moment | [Our addition] |
| 2 | A laptop in WorkWize is missing from the Jamf list | Kept and marked **"No longer in Jamf, last seen with {last owner}"**. Clears itself if it comes back | The brief doesn't cover this. It could mean retired, lost, stolen, or a Jamf error. Flagging is safe in all four cases; deleting is not | [Our addition] |

## How the sync behaves

| # | Situation | Decision | Why | Tag |
|---|---|---|---|---|
| 3 | Same laptop, same person | Matched by serial number and email. Emails are lowercased, serials uppercased, spaces trimmed | "Alex.Smith@…" and "alex.smith@…" are one person | [Required by brief] + normalisation [Our addition] |
| 4 | New laptop with no owner in Jamf | Not imported; shown as skipped with the reason | Brief rule | [Required by brief] |
| 5 | Something changes in Jamf (RAM, owner, name) | Copied over on the next sync | Jamf is the source of truth | [Required by brief] |
| 6 | Jamf removes a value (e.g. a phone number) | Removed here too, shown as "Not provided by Jamf" | Keeping an old value would show data that was deleted on purpose, maybe for privacy | [Our addition] |
| 7 | A name is missing | A person's name falls back to their email; a laptop's name falls back to its model, then "Unknown device ({serial})" | Screens are never blank | [Our addition] |
| 8 | The Jamf file can't be read (missing, broken), or holds a different number of laptops than its `totalCount` says | Nothing changes. A clear error is shown and the attempt is recorded | A half-read list must never change data. `totalCount` covers all pages of a real Jamf API, so the check counts after every page is collected. No `totalCount` → no check | [Our addition] |
| 9 | The list is readable but empty or much shorter | Applied. Missing laptops are flagged (row 2), never deleted. The next good sync clears the flags | Real retirements shouldn't be blocked; the worst case is flags, not lost data | [Our addition] |
| 10 | One broken entry (no serial, invalid email, same serial twice) | Only that entry is skipped, with the reason. An invalid email counts as broken, not "no owner" | One bad entry shouldn't block the rest. A typo in Jamf must not send someone to collect a laptop that's still in use | [Our addition] |
| 10a | The same person appears on several laptops with different details in one list (e.g. phone on one entry, blank on another) | Their details come from their **first** entry in the list; later entries in the same sync don't overwrite them | One clear, predictable rule instead of the last entry silently winning | [Our addition] |
| 11 | Sync pressed twice | The second one is refused: "A sync is already running" | Two syncs at once could collide | [Our addition] |
| 12 | Record of syncs | Every sync is stored: when, result, counts, skips | Lets you see what happened and when | [Our addition] |
| 13 | Laptop hasn't checked in with Jamf for a long time | Warning only, after 30 days (Jamf's own guidance). Measured against the newest check-in in the same list, not today | The mock data is from January 2026; comparing to today would flag every laptop | [Our addition] |

## Deleting

| # | Situation | Decision | Why | Tag |
|---|---|---|---|---|
| 14 | Delete a laptop or person | Removed from WorkWize only (really removed, not hidden). Comes back on the next sync if Jamf still lists it | Brief rule. A real delete avoids problems when the sync recreates the record | [Required by brief] |
| 15 | Delete a person who still has laptops | Allowed, after a warning that says they'll come back and how to remove them for good (in Jamf). Their laptops stay, marked "Owner removed in WorkWize" | Blocking it would break the brief's "re-sync recreates deleted employees", since every person in the mock data has a laptop | [Our addition] |
| 16 | Delete a "To be collected" or "No longer in Jamf" laptop | Removed from WorkWize. Won't come back unless Jamf assigns it to someone again | Jamf no longer assigns it, so the sync has nothing to recreate. Usually this means "collected or retired, done" | [Our addition] |

## Screens

- After a sync, a message stays at the top until closed. Anything that needs a person is listed first; quiet days get one line. **[Our addition]**
- A "Needs attention" button filters the list to laptops that need action, and disappears when there are none. **[Our addition]**
- Laptop page: the essentials first, everything else under "Additional information". **[Our addition]**

## Other assumptions

- WorkWize can't know whether someone left the company, so people are never removed automatically.
- Jamf data is read-only here: no editing (brief), and nothing is written back to Jamf.
- A person listed on several laptops with different details: the first entry in the list is used for that sync.
- Storage isn't in the mock data, so it isn't shown.
- One company, no login.
- The data comes from the provided mock file, not a live Jamf connection (so no pagination or login to Jamf).
- WorkWize's template link returned a 404, so this repo was created fresh and their files were copied in unchanged.
- WorkWize's brief is kept unchanged as `BRIEF.md`; `README.md` holds our own run instructions. **[Our addition]**
