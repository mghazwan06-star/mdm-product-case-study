# Ideas, inefficiencies and known limitations

[Back to README](../README.md) · [All docs](../README.md#all-the-docs)

## Known limitations / shortcuts

Every trade-off accepted in [DECISION-RECORDS.md](DECISION-RECORDS.md) has a row here, in the same words. The other rows are technical shortcuts.

| Limitation | Why we accepted it | What we'd do with more time |
|---|---|---|
| The backend runs on Laravel's built-in development server, and passwords in `docker-compose.yml` are local-only values | The brief asks for a local development setup only | A production web server (e.g. Nginx + PHP-FPM) and secrets kept outside the repo |
| If Jamf ever sends an empty list by mistake, every laptop is flagged "No longer in Jamf" until the next good sync | Nothing is deleted and the next good sync clears the flags; refusing short lists would also block real retirements ([`DECISIONS.md`](DECISIONS.md) row 9) | Ask before applying a sync that would flag most laptops ("This sync would flag 5 of 7 laptops. Apply anyway?") |
| The sync looks up each laptop and person one at a time | Measured in testing 5.3: about 3 database queries per laptop; 5,000 laptops sync in about 3 seconds, 12,000 in about 7. Fast enough for a mid-size customer | Look up all existing laptops and people in one go before saving |
| The laptops and people lists come back whole (no paging, no search) | Measured in testing 5.3: 5,000 laptops appear in about a second (the list itself is 848 KB and takes 0.19 s), but the page is about 380 screens long, so finding one laptop means scrolling. The "Needs attention" filter still works as a shortcut | Page the API lists and the screens; add search and filters |
| Pagination isn't implemented because the mock is one page; the count check is already written for the multi-page case | The brief provides a single-page mock file | Fetch every page from the real Jamf API, then run the same count check |
| Laravel's standard `users` table exists but isn't used (no login) | Kept Laravel's defaults; its `cache` tables will hold the "sync already running" lock | Add accounts and login, or remove the table |
| The sync reads the whole Jamf file into memory, so it fails above about 12,000 laptops: 12,000 synced in about 7 seconds, 13,000 ran out of memory (testing 5.3, finding F15). Nothing is saved, and since the fixes the customer sees "Something went wrong during the sync. Nothing was changed." | Muhammad decided to keep it a limitation: far above a mid-size customer (5,000 laptops sync in about 3 seconds), and a live Jamf connection reads page by page anyway. The 30-second time limit is never the problem first | Process the list page by page |
| A person's name, job title or phone sent in a form WorkWize can't use (e.g. a list instead of text) is left blank; the "couldn't read" label only covers laptop fields | Extremely rare; Muhammad chose to keep the label to the laptop page | Store and show unreadable person details the same way |
| After a very large change, the sync message lists every laptop that needs attention (6,871 lines in testing 5.4, finding F14) | Muhammad decided to keep it a limitation: only at large scale, and the message is still true | Show the first 10, then "and 6,861 more" linking to the Needs attention filter |
| "To be collected" is a label, not yet a collection workflow | It marks the exact moment a laptop needs collecting; scheduling and shipping are a product of their own (decision 1) | Turn it into a collection order: scheduling, shipping labels, tracking |
| Brand-new laptops without an owner in Jamf are never imported | Brief rule. Only a laptop WorkWize already knows becomes "To be collected" (decision 1) | Ask WorkWize whether customers want to see spare, unassigned laptops |
| Retired laptops stay flagged "No longer in Jamf" until someone deletes them | Flagging is the only option that's safe if the laptop was lost, stolen or dropped by a Jamf error. It's one delete each, and they don't come back (decision 2) | A "retired" status that survives syncs |
| Deleted laptops and people come back on the next sync if Jamf still lists them, including a person deleted after the warning | Brief rule: delete is local only. The warning says how to remove them for good, in Jamf (decision 3) | "Hide" or "retired" that survives syncs |
| A Jamf glitch that blanks a field (e.g. a phone number) blanks it here too, until the next good sync | Jamf decides what's true, and a blank is usually on purpose. Names never go blank: they fall back to the email or the model (decision 5) | A preview when a sync would blank many fields at once |
| Storage isn't shown | It isn't in Jamf's data | Add it if a real Jamf connection provides it |
| The "hasn't checked in" warning (designed, not built) is measured against the newest check-in in the list, not today, and stale laptops stay out of "Needs attention" | The mock data is from January; measured against today, every laptop would be stale on the first sync. An unused laptop is often fine (a spare, someone on holiday), so it's a warning, not a task (decision 6.2c) | Build it, and add a separate "worth checking" list |
| A delete really removes the record and keeps no trace of what was deleted | Simplest way to make sure a re-sync can bring it back without clashes (quick approval 1) | An audit log of deletes: who, what, when |
| The status labels say "Jamf" as fixed text ("No longer in Jamf", "Not provided by Jamf"); only the skip reasons use the tool's name | Jamf is the only tool today | Use the tool's name in the labels when a second tool is added |
| No dark mode; the website uses the system font | A small internal tool; the visual redesign kept to one light look (Impeccable's audit noted it) | Add a dark palette from the same colour tokens |

## Improvement ideas
| Idea | Who benefits | Effort |
|---|---|---|
| Thousands separators: "4,897 unchanged" instead of "4897" (testing 5.3) | Larger customers, who read these numbers every day | Small |
| An alert (email or Slack) when a sync is interrupted or fails (testing 5.3, 5.4). Since fix F10 the history marks it "interrupted", but nobody is told | IT teams, who would otherwise only notice on the next visit | Medium |

## Inefficiencies noticed
| Where | What | Fix idea |
|---|---|---|
