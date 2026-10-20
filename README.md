# MDM Device Sync

*A case study set by WorkWize during their hiring process for a Full Stack Developer role, shared publicly with their permission. Not a WorkWize product.*

**Keeping a company's laptop records true when the system that manages the laptops changes underneath them.**

Companies track their work laptops in a device-management tool such as Jamf. This product copies those laptops and their owners into an asset system, every time someone presses Sync, and decides what to do when the data changes: someone leaves, a laptop disappears, the list arrives broken.

**My role: product owner.** I framed the problem, made every product decision and wrote down why, set the rules for how AI built it, and approved every step before it was built. AI wrote the code (claude.ai for thinking and review, Claude Code for building), credited openly on every save point. I have only basic Python and SQL, and no PHP; my experience is in sales, product and AI systems. [How it was built](docs/HOW-WE-BUILT-THIS.md)

| At a glance | |
|---|---|
| Product decisions | 6 major ones, each with the options turned down and my reasons, plus 8 quick approvals |
| Principles behind them | 18, in my own words, traced to the code and tests |
| Automated tests | 250 (133 backend, 117 website), all passing |
| Deliberate breaks | 82: a rule switched off on purpose to prove a test catches it; all caught |
| Problems found in testing | 16; 14 fixed, 2 accepted as limitations with reasons |
| Save points | 102 small, honest steps, 11 to 16 Oct 2026 |

*WorkWize's brief is not included in this public copy; the decision records say what each rule needed.*

## The problem

WorkWize helps companies, often with remote teams, buy, ship, track and collect back work laptops. Their customers already run Jamf, which every company laptop reports to: its serial number, its hardware, who it's assigned to. The customer wants those laptops in WorkWize without typing them in.

Copying the list once is easy. The product questions start on the second sync, when the list has changed:

- **Someone leaves.** Jamf now shows their laptop with no owner. The simple rule, "skip laptops without an owner", would hide the exact moment the laptop needs collecting, which is WorkWize's business.
- **A laptop vanishes from the list.** It could be retired, lost, stolen or a Jamf error. Deleting it would be wrong in three of those four cases.
- **The list arrives broken or odd.** A half-read list, a serial number with a space, a battery at 140%. Guessing makes the records quietly wrong; refusing everything blocks every customer.
- **Someone deletes a person in WorkWize** who still has laptops in Jamf. The next sync brings them back. What should happen in between, and what should the screen say?

Each of these is a trade-off between keeping data safe, keeping it honest and keeping the everyday experience simple. That's what this case study is about.

## Start here (about 10 minutes)

1. **This page** (2 min): "How this was designed", "Three product calls" and the two diagrams.
2. [docs/DECISION-RECORDS.md](docs/DECISION-RECORDS.md) (3 min): the six product decisions with the options turned down, the reasons and the changes of mind; the laptop status diagram and the screens that prove each decision.
3. [docs/HOW-WE-BUILT-THIS.md](docs/HOW-WE-BUILT-THIS.md) (2 min): who did what, the build loop, the catches and the mistakes, on every side.
4. [docs/TEST-LOG.md](docs/TEST-LOG.md) (1 min): "In one minute" at the top.
5. [docs/WHAT-I-WOULD-DO-NEXT.md](docs/WHAT-I-WOULD-DO-NEXT.md) (2 min): what's designed but not built, and where the business value is.
6. For engineers: the [code tour](docs/ENGINEERING.md#code-tour-about-10-minutes), six files to open first.

## All the docs

| File | What it shows | Most useful for |
|---|---|---|
| [DECISION-RECORDS.md](docs/DECISION-RECORDS.md) | The six product decisions: options, choice, reasons, changes of mind, trade-offs, with diagrams and screenshots | Product and hiring managers |
| [HOW-WE-BUILT-THIS.md](docs/HOW-WE-BUILT-THIS.md) | Roles, the rules for using AI, the build loop, catches and mistakes by every side | Anyone checking how AI was used |
| [PRINCIPLES.md](docs/PRINCIPLES.md) | The 18 principles in Muhammad's words, traced to code and tests | Product and engineering |
| [DECISIONS.md](docs/DECISIONS.md) | Every rule in one line, with built or designed-not-built status | Engineers |
| [PRODUCT-NOTES.md](docs/PRODUCT-NOTES.md) | The delete puzzle, the leaver moment, messy Jamf data, business value | Product and business |
| [WHAT-I-WOULD-DO-NEXT.md](docs/WHAT-I-WOULD-DO-NEXT.md) | Designed but not built; business, product, scale, more tools | Product and engineering leads |
| [TEST-LOG.md](docs/TEST-LOG.md) | Every scenario, odd data, scale, crashes; the 16 findings with before and after | Engineers |
| [MISTAKES.md](docs/MISTAKES.md) | Eight mistakes and what they taught | Anyone |
| [IDEAS-AND-LIMITATIONS.md](docs/IDEAS-AND-LIMITATIONS.md) | Known limitations and the trade-offs that were accepted | Engineers and product |
| [ENGINEERING.md](docs/ENGINEERING.md) | Run the app and the tests, the code tour, the sync design and its improvements over the reference | Engineers |
| [CHANGELOG.md](docs/CHANGELOG.md) | Every save point, newest first | Engineers |

## How I worked

1. **Understand before deciding.** I mapped the business, the data flow and the tricky cases on a [Miro board](https://miro.com/app/board/uXjVEb0E9IA=/) before anything else.
2. **Decide before building.** Every product decision was made and written down, with the options I turned down and why, before any code existed: [the decision records](docs/DECISION-RECORDS.md).
3. **Principles, not one-off answers.** The decisions rest on [18 principles](docs/PRINCIPLES.md) in my own words. The main one: *Jamf decides what's true; WorkWize decides how to show it and what to do about it.*
4. **Build in tiers.** Tier 1 was everything the brief asked for, Tier 2 the core improvements. Each tier was finished and tested before the next, so there was always a version I could hand in. Tiers 3 and 4 are fully designed but not built, because of time.
5. **Prove, don't claim.** A test only counts if it can fail, so for each rule we switched the rule off on purpose and checked that a test failed. Then I had it tested with odd data, up to 25,000 laptops, and crashes mid-sync, and decided what to do about each of the 16 problems found.

**How AI was used.** I set the rules: a human decides, AI proposes and checks. Every step went through the same loop:

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

## Four product calls

Each one in short: the situation, the options, my call and the price I accepted. The full records, with every quote, change of mind and screenshot: [DECISION-RECORDS.md](docs/DECISION-RECORDS.md).

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

## What shipped, and how it was proven

**What it does today:** one Sync Now button imports every assigned laptop and its owner from Jamf. A list of laptops shows each one's status ("Assigned to …", "To be collected from …", "No longer in Jamf · last seen with …"), with what needs a person first. There's a page per laptop, a list of people, and deletes with a plain warning. Every sync is recorded.

**How it was proven:**
- **250 automated tests**, all passing, and **82 deliberate breaks**, all caught.
- **Tested beyond the happy path:** 32 agreed scenarios, 19 kinds of odd but realistic Jamf data, 1,000 to 25,000 laptops, and crashes in the middle of a sync. Data was never half-saved in any test, including real crashes.
- **16 problems found, and I decided each one.** The worst: one over-long name in Jamf stopped the whole sync for everyone; after any crash the app said "A sync is already running" for 5 minutes; raw database errors reached the screen. 14 were fixed, each with a test and a before-and-after. 2 are accepted as limitations, because they only matter far above a mid-size customer (5,000 laptops sync in about 3 seconds). [TEST-LOG.md](docs/TEST-LOG.md)

### What it looks like

*Screenshots were taken on 16 Oct 2026. The top bar has changed since: it now shows the app's name, "MDM Device Sync".*

**After a sync, what needs a person comes first**, by name and with a link: here a leaver's laptop to collect and a laptop that disappeared from Jamf.

![To be collected and No longer in Jamf](docs/screenshots/to-be-collected-and-no-longer-in-jamf.jpg)

**When Jamf sends something WorkWize can't use**, it says so and shows what Jamf sent, instead of guessing or hiding it.

![Jamf sent a value WorkWize couldn't read](docs/screenshots/f3-f7-f8-after-couldnt-read-label.jpg)

**When something breaks, the message is true and says what to do.** Before and after, from testing:

| Before (shows the earlier behaviour and look) | After |
|---|---|
| ![Raw database error](docs/screenshots/f12-before-raw-database-error.jpg) | ![Plain message](docs/screenshots/f12-after-plain-message.jpg) |
| ![Already running (untrue)](docs/screenshots/f10-before-already-running.jpg) | ![The last sync was interrupted](docs/screenshots/f10-after-interrupted.jpg) |

More pictures, next to each test case: [docs/TEST-LOG.md](docs/TEST-LOG.md).

### Built, and designed but not built

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

## For engineers

How to start the app, run the tests and try a scenario, a six-file code tour, and how the sync service is designed: [docs/ENGINEERING.md](docs/ENGINEERING.md). In short: `docker compose up`, then open http://localhost:5173 and press **Sync Now**.

## Licence

All rights reserved: you may view this repository, but not copy or reuse it without permission. See [LICENSE](LICENSE). The case study was set by WorkWize, who allowed this public copy; the WorkWize name belongs to WorkWize.
