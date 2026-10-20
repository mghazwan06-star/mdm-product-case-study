# MDM Device Sync

*A product case study set by WorkWize during their hiring process, shared publicly with their permission. Not a WorkWize product.*

![Overview. Laptops leave with people; the records should say so. The question: how should a sync treat change, so the records stay safe, honest and easy to act on? My answer, as three rules: never delete on a sync (P3); flag what needs a person, by name (P5); change nothing when the list can't be read in full (P8). Proof: 6 decisions before any code; 250 tests and 82 deliberate breaks; 16 problems found, 14 fixed. Next step: turn To be collected into a collection order.](docs/images/slides/01-overview.png)

**Keeping a company's laptop records true when the system that manages the laptops changes underneath them.**

Companies track their work laptops in a device-management tool such as Jamf. This product copies those laptops and their owners into an asset system, every time someone presses Sync, and decides what to do when the data changes: someone leaves, a laptop disappears, the list arrives broken.

**In 30 seconds**
- **The problem:** when people leave or laptops vanish, a simple copy from Jamf hides exactly the moments someone needs to act.
- **My calls:** never delete on a sync; flag what needs a person, by name; change nothing when the list can't be read in full.
- **The proof:** 6 product decisions written down before any code, 250 automated tests, and 16 problems found in testing with a decision on each.

**My role: product owner.** I framed the problem, made every product decision and wrote down why, set the rules for how AI built it, and approved every step before it was built. AI wrote the code (claude.ai for thinking and review, Claude Code for building), credited openly on every save point (a save point is one small saved version of the work). My background is in sales, product and AI systems. [How it was built](docs/HOW-WE-BUILT-THIS.md)

*WorkWize's brief (the assignment) is not included in this public copy; the decision records say what each rule needed.*

**Jump to:** [The problem](#the-problem) · [See it work](#see-it-work) · [How I think](#how-i-think) · [Four product calls](#four-product-calls) · [How it was proven](#how-it-was-proven) · [Business value](#where-the-business-value-is-and-what-id-do-next) · [What I learned](#what-i-learned) · [All the docs](#all-the-docs)

**Read by role**

| You are | Read | Time |
|---|---|---|
| Anyone | "In 30 seconds" above, the demo, then [How I think](#how-i-think) and [Four product calls](#four-product-calls) | 5 min |
| Product | [How I think](#how-i-think) → [Four product calls](#four-product-calls) → [the decision records](docs/DECISION-RECORDS.md) → [product notes](docs/PRODUCT-NOTES.md) → [what I'd do next](docs/WHAT-I-WOULD-DO-NEXT.md) | 15 min |
| Operations or finance | [How I worked](#how-i-worked) → [how it was built, with the numbers](docs/HOW-WE-BUILT-THIS.md) → [the test log's one-minute summary](docs/TEST-LOG.md#in-one-minute) → [limitations and the trade-offs accepted](docs/IDEAS-AND-LIMITATIONS.md) | 15 min |
| Engineering | [For engineers](docs/ENGINEERING.md): run it, the code tour, the sync design | 10 min |

## The problem

WorkWize helps companies, often with remote teams, buy, ship, track and collect back work laptops. Their customers already run Jamf, which every company laptop reports to: its serial number, its hardware, who it's assigned to. The customer wants those laptops in WorkWize without typing them in.

Copying the list once is easy. The product questions start on the second sync, when the list has changed:

- **Someone leaves.** Jamf now shows their laptop with no owner. The simple rule, "skip laptops without an owner", would hide the exact moment the laptop needs collecting, which is WorkWize's business.
- **A laptop vanishes from the list.** It could be retired, lost, stolen or a Jamf error. Deleting it would be wrong in three of those four cases.
- **The list arrives broken or odd.** A half-read list, a serial number with a space, a battery at 140%. Guessing makes the records quietly wrong; refusing everything blocks every customer.
- **Someone deletes a person in WorkWize** who still has laptops in Jamf. The next sync brings them back. What should happen in between, and what should the screen say?

Each of these is a trade-off between keeping data safe, keeping it honest and keeping the everyday experience simple. That's what this case study is about.

## See it work

**What it does today:** one Sync Now button imports every assigned laptop and its owner from Jamf. A list of laptops shows each one's status ("Assigned to …", "To be collected from …", "No longer in Jamf · last seen with …"), with what needs a person first. There's a page per laptop, a list of people, and deletes with a plain warning. Every sync is recorded.

![Demo: the laptop list, all assigned. Sync Now is pressed after Emma leaves and the Design Team MacBook disappears from Jamf. The message lists both laptops by name first, the statuses change to To be collected and No longer in Jamf, and Needs attention filters the list to those two.](docs/images/demo-sync.gif)

*Recorded in the app on 20 Oct 2026, with test data in which Emma leaves and a laptop disappears from Jamf.*

## How I worked

1. **Understand before deciding.** I mapped the business, the data flow and the tricky cases on a [Miro board](https://miro.com/app/board/uXjVEb0E9IA=/) before anything else.
2. **Decide before building.** Every product decision was made and written down, with the options I turned down and why, before any code existed: [the decision records](docs/DECISION-RECORDS.md).
3. **Principles, not one-off answers.** The decisions rest on [18 principles](docs/PRINCIPLES.md) in my own words. The main one: *Jamf decides what's true; WorkWize decides how to show it and what to do about it.*
4. **Build in tiers.** Tier 1 was everything the brief asked for, Tier 2 the core improvements. Each tier was finished and tested before the next, so there was always a version I could hand in. Tiers 3 and 4 are fully designed but not built, because of time.
5. **Prove, don't claim.** A test only counts if it can fail, so for each rule we switched the rule off on purpose and checked that a test failed. Then I had it tested with odd data, up to 25,000 laptops, and crashes mid-sync, and decided what to do about each of the 16 problems found.
![Save points per day: 11 Oct 1 (understand, decide, set up), 12 Oct 25 (build Tier 1), 13 Oct 21 (Tier 2 and testing), 14 Oct 0, 15 Oct 26 (fix 14 findings), 16 Oct 29 (documents, redesign, final audits).](docs/images/slides/02-timeline.png)

**How decisions were made.** I set one rule for the AI tools: they propose and check, I decide. Every step went through the same loop:

```mermaid
flowchart LR
    P["Claude Code<br/>plans the step"] --> R["claude.ai<br/>reviews the plan"]
    R --> A{"I approve<br/>or change it"}
    A -- change --> P
    A -- approve --> B["Claude Code builds"]
    B --> T["Tests, plus a<br/>deliberate break"]
    T --> S["One save point<br/>and the logbook"]
```

*Nothing was built without my OK. Mistakes on every side, mine and the AI's, are written down in [MISTAKES.md](docs/MISTAKES.md) and [HOW-WE-BUILT-THIS.md](docs/HOW-WE-BUILT-THIS.md).*

## How I think

Before deciding anything, I wrote down the rules I wanted every decision to follow. Each product call then came from one of them, and I checked the calls against each other so that one decision makes another one safe. 18 principles in all, in my own words: [PRINCIPLES.md](docs/PRINCIPLES.md).

![How I think. Four of my principles, my words, and the call each settled. P5, a status should lead to action ("if it just clears the owner, then no action is being taken"): call 1, mark the leaver's laptop To be collected. P3, never lose data silently ("we keep it so at least nothing is lost, which is the priority"): call 2, flag a vanished laptop, never delete. P12, the brief comes before my preferences, learned when I changed my own first choice: call 3, explain how to remove a person for good in Jamf, then delete. P8, all or nothing for unreadable input ("we give either 100% or nothing"): call 4, an unreadable list changes nothing. How the calls hold each other up: because a sync never deletes, an empty list can be applied safely; To be collected only comes from a change in Jamf, so deleting a person never sends anyone to collect a laptop; both flags keep the last owner, so every flag says who to talk to.](docs/images/slides/03-how-i-think.png)

## Four product calls

Each one in short: the situation, the options, my call and the price I accepted. The full records, with every quote, change of mind and screenshot: [DECISION-RECORDS.md](docs/DECISION-RECORDS.md).
![Four calls and the options weighed. 1, someone leaves: keep the laptop and mark it To be collected (not remove it, not just clear the owner). 2, a laptop vanishes: flag it No longer in Jamf (not remove, not ignore). 3, a person with laptops is deleted: first choice was to refuse the delete, changed to a warning with instructions, then the delete. 4, the list arrives empty or short: apply it, because the worst case is flags, never lost data.](docs/images/slides/04-four-calls.png)

### 1. Someone leaves the company

**Situation:** Emma leaves; IT removes her as the owner in Jamf. Her laptop still exists, with no owner.

**Options:** remove the laptop · keep it, owner cleared · keep it and mark it "To be collected".

**My call:** mark it **"To be collected from Emma Johnson"**. With the other two, nobody acts, and collecting laptops is WorkWize's business.
> "If it just clears the owner and it's written 'assigned to nobody', then no action is being taken, even though the copy is right."

**Price accepted:** it's a label, not yet a collection workflow.

### 2. A laptop vanishes from Jamf

**Situation:** a laptop that was in last week's list isn't there today: retired, lost or stolen, re-enrolled, or a Jamf error.

**Options:** remove it · flag it "No longer in Jamf" · do nothing.

**My call:** flag it, with the last owner's name. It's the only option that's safe in all four cases. A sync never deletes anything.
> "If it was lost or stolen, nobody would be notified."

**Price accepted:** retired laptops stay flagged until someone deletes them, one click each.

### 3. Deleting a person who still has laptops

**Situation:** someone deletes Alex, who still has two laptops in Jamf. By the brief's rule, the next sync brings him back.

**Options:** delete him and his laptops · delete him, keep the laptops · refuse the delete · warn with instructions, then delete.

**My call:** I first chose to refuse. Then claude.ai pointed out that every person in the data has a laptop, so nobody could ever be deleted, and the brief's own rule could never be shown. **I changed my mind:** a warning that explains how to remove him for good (in Jamf), then the delete, with his laptops kept.

**What it taught me:** the brief's requirements come before my own preferences. It became one of the principles.

### 4. The list arrives broken

**Situation:** the Jamf list is unreadable, cut off, or suspiciously short.

**My call:** an unreadable or cut-off list changes nothing, all or nothing, and says so plainly. An empty or short list is applied, because thanks to call 2 the worst case is flags, never lost data, and the next good sync clears them.
> "We give either 100% or nothing."

**Price accepted:** a Jamf hiccup that sends an empty list flags every laptop until the next good sync.

### How the calls fit together

![The four laptop statuses. Assigned moves to To be collected when Jamf shows no owner, to No longer in Jamf when it is missing from the list, and to Owner removed in WorkWize when a person deletes the owner. Any status returns to Assigned when Jamf lists the laptop with an owner again. A sync never deletes a laptop.](docs/images/slides/05-status-system.png)

## How it was proven

![At a glance: 6 product decisions made before any code; 18 principles in my own words; 250 automated tests, all passing; 82 deliberate breaks, all caught; 16 problems found in testing, 14 fixed and 2 accepted; 102 save points from 11 to 16 Oct 2026. Each number names the document that proves it.](docs/images/slides/06-evidence.png)

- **Tested beyond the happy path:** 32 agreed scenarios, 19 kinds of odd but realistic Jamf data, 1,000 to 25,000 laptops, and crashes in the middle of a sync. Data was never half-saved in any test, including real crashes.
- **16 problems found, and I decided each one.** The worst: one over-long name in Jamf stopped the whole sync for everyone; after any crash the app said "A sync is already running" for 5 minutes; raw database errors reached the screen. 14 were fixed, each with a test and a before-and-after. 2 are accepted as limitations, because they only matter far above a mid-size customer (5,000 laptops sync in about 3 seconds). [TEST-LOG.md](docs/TEST-LOG.md)

![Testing found 16 problems: 14 fixed, 2 accepted with reasons. Grouped as could stop every sync or show something untrue (F1, F2, F4, F9, F10, F11, F12, F13, all fixed), needed a product decision (F3, F5, F6, F7, all fixed), and rare data or very large scale only (F8 and F16 fixed; F14 and F15 accepted as limitations).](docs/images/slides/07-findings.png)

## Where the business value is, and what I'd do next

The sync is the foundation; the value is in what it makes visible.

| Opportunity | Why it's close | First step |
|---|---|---|
| **Collection as a service** | "To be collected" already marks the exact moment a laptop needs collecting, and collection is part of what WorkWize sells | Turn the flag into a collection order: scheduling, shipping, tracking |
| **Replacement offers** | Purchase date, battery health and model are already stored for every laptop | Spot ageing or worn laptops and offer a replacement before they fail |
| **Jamf data health check** | Testing showed small mistakes in a customer's Jamf (a serial with a space, a number saved as text) cause wrong results without anyone noticing | A monthly "your device data has 3 issues" report for the customer's IT team |
| **Fleet reports** | Model, age, status and owner are already in one place | Counts finance and IT can export |

**Before selling to larger customers:** a live Jamf connection read page by page (today a sync breaks between 12,000 and 13,000 laptops; 5,000 take about 3 seconds), syncs on a schedule, and accounts with each customer's data kept separate.

**Questions I'd take to the team first:** should WorkWize ever write changes back to Jamf? And how can it know who actually left, since Jamf only reports devices (a connection to HR)?

The full list, including the features designed but not built: [WHAT-I-WOULD-DO-NEXT.md](docs/WHAT-I-WOULD-DO-NEXT.md).

## What it looks like

![After a sync, what needs a person comes first. The laptop list with five numbered notes: 1, Needs attention (2) filters the list; 2, the message names each laptop before the counts; 3, No longer in Jamf, last seen with Lucas Brown, flagged and never deleted; 4, To be collected from Emma Johnson, the leaver moment as an action; 5, each info symbol explains the status. Quote: if it just clears the owner and says assigned to nobody, then no action is being taken.](docs/images/slides/08-the-screen.png)

**When Jamf sends something WorkWize can't use**, it says so and shows what Jamf sent, instead of guessing or hiding it.

![Jamf sent a value WorkWize couldn't read](docs/screenshots/f3-f7-f8-after-couldnt-read-label.jpg)

![Every message has to be true and say what to do next. Four before-and-afters: database stopped, from a raw SQL error to "WorkWize's database isn't reachable right now. Nothing was changed. Try again in a minute."; after a crash, from an untrue "A sync is already running" to "The last sync was interrupted…"; backend crash, from "Couldn't reach WorkWize" to "Something went wrong during the sync…"; frozen backend, from endless "Loading people…" to "This is taking longer than usual…" after 15 seconds.](docs/images/slides/09-messages.png)

**The same fixes on screen**, before and after, from testing:

| Before (shows the earlier behaviour and look) | After |
|---|---|
| ![Raw database error](docs/screenshots/f12-before-raw-database-error.jpg) | ![Plain message](docs/screenshots/f12-after-plain-message.jpg) |
| ![Already running (untrue)](docs/screenshots/f10-before-already-running.jpg) | ![The last sync was interrupted](docs/screenshots/f10-after-interrupted.jpg) |

More pictures, next to each test case: [docs/TEST-LOG.md](docs/TEST-LOG.md).

*Screens re-captured on 20 Oct 2026 from the same scenarios; "before" pictures are unchanged and show the earlier behaviour.*

## Built, and designed but not built

Tier 1 (the brief) and Tier 2 (core improvements) are built and tested. Tier 3 (polish) and Tier 4 (stretch) were planned and fully designed, but not built because of time. Each has its decision, my reasoning and its principles written down in [docs/DECISIONS.md](docs/DECISIONS.md), so it can be built as designed.

```mermaid
flowchart LR
    U["1. Understand"] --> D["2. Decide"]
    D --> S["3. Set up"]
    S --> B
    subgraph B["4. Build in tiers"]
        direction TB
        T1["Tier 1: the brief<br/>built and tested"] --> T2["Tier 2: core improvements<br/>built and tested"]
        T2 --> T3["Tier 3: polish<br/>designed, not built (time)"]
        T3 --> T4["Tier 4: PDF per sync<br/>designed, not built (time)"]
    end
    B --> X["5. Test"]
    X --> Y["6. Show the thinking"]
    style T3 stroke-dasharray: 5 5
    style T4 stroke-dasharray: 5 5
```

*The order of the work. Tiers 1 and 2 are built and tested; Tiers 3 and 4 (dashed) are designed, not built, because of time.*

The seven features that are designed but not built, each with its decision and principles: [WHAT-I-WOULD-DO-NEXT.md](docs/WHAT-I-WOULD-DO-NEXT.md#designed-not-built). The status of every decision: [DECISIONS.md](docs/DECISIONS.md).

## What I learned

- **The brief comes before my preferences.** My first choice for deleting a person was well reasoned, but it made one of the brief's own rules impossible to show. I changed it, kept the idea behind it (give instructions, not just a warning), and wrote the lesson down as a principle (P12).
- **Think in systems, not single rules.** Applying an empty list from Jamf looks risky on its own. Because a sync never deletes, the worst case is flags that clear on the next good sync. One decision made the other safe, so I checked every call against the others before anything was built.
- **A status is only useful if someone acts on it.** "Assigned to nobody" was accurate and useless. "To be collected from Emma" is the exact moment WorkWize's collection service is needed.
- **Writing decisions down word for word made contradictions easy to see,** including my own first choice in call 3.
- **Small steps made every mistake cheap.** Each one was found in the step that caused it.
- **Look at the screens, not only the tests.** After a fix, a very long name covered other people's names. Every test passed; a screenshot found it.
- **Write down only what is built.** The worst audit finding wasn't a bug: a document described planned features as if they worked.

The full record, including every mistake: [MISTAKES.md](docs/MISTAKES.md) · [HOW-WE-BUILT-THIS.md](docs/HOW-WE-BUILT-THIS.md)

## All the docs

| File | What it shows | Most useful for |
|---|---|---|
| [DECISION-RECORDS.md](docs/DECISION-RECORDS.md) | The six product decisions: options, choice, reasons, changes of mind, trade-offs, with diagrams and screenshots | Product and hiring managers |
| [HOW-WE-BUILT-THIS.md](docs/HOW-WE-BUILT-THIS.md) | Roles, the rules for using AI, the build loop, catches and mistakes by every side | Anyone checking how AI was used |
| [PRINCIPLES.md](docs/PRINCIPLES.md) | The 18 principles in my own words, traced to code and tests | Product and engineering |
| [DECISIONS.md](docs/DECISIONS.md) | Every rule in one line, with built or designed-not-built status | Engineers |
| [PRODUCT-NOTES.md](docs/PRODUCT-NOTES.md) | The delete puzzle, the leaver moment, messy Jamf data, business value | Product and business |
| [WHAT-I-WOULD-DO-NEXT.md](docs/WHAT-I-WOULD-DO-NEXT.md) | Designed but not built; business, product, scale, more tools | Product and engineering leads |
| [TEST-LOG.md](docs/TEST-LOG.md) | Every scenario, odd data, scale, crashes; the 16 findings with before and after | Engineers |
| [MISTAKES.md](docs/MISTAKES.md) | Eight mistakes and what they taught | Anyone |
| [IDEAS-AND-LIMITATIONS.md](docs/IDEAS-AND-LIMITATIONS.md) | Known limitations and the trade-offs that were accepted | Engineers and product |
| [ENGINEERING.md](docs/ENGINEERING.md) | Run the app and the tests, the code tour, the sync design and its improvements over the reference | Engineers |
| [CHANGELOG.md](docs/CHANGELOG.md) | Every save point, newest first | Engineers |

## For engineers

How to start the app, run the tests and try a scenario, a six-file code tour, and how the sync service is designed: [docs/ENGINEERING.md](docs/ENGINEERING.md). In short: `docker compose up`, then open http://localhost:5173 and press **Sync Now**.

## Licence

All rights reserved: you may view this repository, but not copy or reuse it without permission. See [LICENSE](LICENSE). The case study was set by WorkWize, who allowed this public copy; the WorkWize name belongs to WorkWize.
