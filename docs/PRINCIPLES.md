# Principles

These are the rules behind every decision in this project. They came from Muhammad's answers while the decisions were made (in sessions with claude.ai, before any code was written), and from a few later moments in the build. The quotes are his words as recorded then, unpolished. **[Our addition]**

The numbers (P1–P18) are used across the docs and the code. "Decision n" refers to the six big decisions made before the build: 1 the leaver, 2 the vanished laptop, 3 deleting a person who has laptops, 4 a broken Jamf list, 5 Jamf removing a value, 6 the screens.

---

### P1: Jamf decides what's true. WorkWize decides how to show it and what to do about it.
> "Jamf is the source of truth and is the final decision, right? However, how we show it and the context we show the Jamf truth depends on WorkWize."

- **From:** Decision 2 (the vanished laptop).
- **Applied:** [DECISIONS.md](DECISIONS.md) rows 1, 2, 5, 6, 17. WorkWize never edits Jamf's facts; it only labels them.

### P2: Design for the everyday customer. Make the worst case safe and fixable. Don't complicate the everyday experience for rare cases.
> "In each of these decisions, you see the worst case scenario, you see whether it satisfies the average daily customer rather than an edge case customer. So we can minimize decision fatigue and [not] overcomplicate simple processes just because of the edge case and to accommodate it."

> "A would be the best option because it covers most cases most of the time rather than making the system more complicated to adjust for edge cases but then making UI/UX complicated for majority cases."

- **From:** Decisions 5 and 4.
- **Applied:** rows 9, 10, 17, 21, 24. One broken entry is skipped; a harmless format difference never stops a sync; very large customers are a written-down limitation, not extra screens for everyone.

### P3: Never lose data silently. Prefer flagging plus a human in the loop over deleting.
> "We keep it so at least nothing is lost, which is the priority, right? We don't want to remove things. And the customer is notified, so the human in the loop happens."

> "We don't want to do something that deletes data without giving context to the person. This is something that we need to be staying consistent with throughout our decisions."

- **From:** Decisions 2 and 3.
- **Applied:** rows 1, 2, 9, 14–16, 20. A sync never deletes. Found during the fixes: the database treated "jose@…" and "josé@…" as the same person, which would have merged two people's laptops (row 20).

### P4: The data must stay honest. What the website shows must match reality.
> "Even like the front end, the UI React website, is not really showing that that particular client or company actually still has a laptop. So now the data is not honest either."

> "If you just do nothing, then again the data is lying."

- **From:** Decisions 1 and 2.
- **Applied:** rows 1, 2, 6, 18, 19, 20, 22. "To be collected" and "No longer in Jamf"; a value Jamf sent but WorkWize can't read is shown as sent, never guessed; "already running" only while it's true.

### P5: A status should lead to action.
> "If it just clears the owner and it's written 'assigned to nobody', then no action is being taken, even though the copy is right."

- **From:** Decision 1.
- **Applied:** rows 1, 2, 23; the "Needs attention" button; "taking longer than usual" after 15 seconds of waiting.

### P6: Minimum Viable Information (MVI)
> "There are certain fields that just stay regardless. So we put a safety net. So there's a minimum viable information... The name has to be the minimum viable information. So the MVI, we call it."

- **From:** Decision 5.
- **Applied:** row 7. A person always has an email and a name (the name falls back to the email); a laptop always has a serial and a name (falls back to the model).

### P7: One source of truth, applied consistently ("pick your poison").
> "We need at least one source of truth over the other. It's a pick a poison type of scenario... If the Jamf is the source of truth, then it needs to be source of truth for whatever the information is, even if that's the number, the email. Regardless."

- **From:** Decision 5.
- **Applied:** rows 5, 6, 10a. Every Jamf field follows Jamf, including removals; within one sync, a person's details come from their first entry, one predictable rule.

### P8: All or nothing for unreadable input.
> "We give either 100% or nothing."

- **From:** Decision 4.
- **Applied:** row 8. An unreadable or incomplete list changes nothing; a sync that crashes halfway saves nothing (proved in real crashes during testing).

### P9: "To be collected" is triggered only by a change in Jamf, never by an action in WorkWize.
> "To be collected only happens if the Jamf is changed directly, and a final decision is actually made to go through the recollection process."

- **From:** Decision 3.
- **Applied:** rows 1, 10, 15. Deleting a person gives their laptops "Owner removed in WorkWize", not "To be collected"; an invalid email skips the entry instead of raising a false "To be collected".

### P10: Every piece of information has one owner.
> "Imagine it's changed on the React interface. Then what happens? Because on the next sync, it will just go back to 16 or whatever it was before, right?"

- **From:** a question in Phase 1 (understanding the system), before any decision.
- **Applied:** no editing anywhere in WorkWize (brief); wrong data is fixed in Jamf. The API refuses edits with a plain explanation.

### P11: Give context and instructions, not just warnings or refusals.
> "We're not warning them. We're giving them now instructions. If they do want to delete, they have to do certain things first. They can't cut the root before cutting the stem."

- **From:** Decision 3.
- **Applied:** rows 15, 22, 23; every confirmation and error message says what happened and what to do. Technical errors never reach the screen.

### P12: The brief's requirements come before our preferences.
No quote: learned in Decision 3. Muhammad's first choice there (refuse to delete a person who has laptops) was well reasoned, but it would have made a brief requirement ("re-sync recreates deleted employees") impossible to show with WorkWize's own test data. The final decision kept his principle (context and instructions) inside a design that meets the brief.

- **Applied:** row 15; rows 1 and 4 (unassigned laptops are never imported, as the brief says).

### P13: Simple on the surface, complexity only where it's needed ("the right amount of engineering").
> "We design everything for the worst case scenario. But we make sure the best case scenario is easy and direct and is not affected by additional steps. So only when it goes down to the worst case scenario are those additional steps actually seen and made more complicated, because that complication is needed. That's not over engineering. That's the right number of engineering. But if we show everything at once, even for a normal day, then that's over engineering."

> "Just because we're making stuff simple for the daily user does not mean all of the information will not be there. It will be there, but... you can only really see it if you intentionally want to see it."

- **From:** Decision 6 (the sync message and the laptop page).
- **Applied:** the screens section of [DECISIONS.md](DECISIONS.md): zero counts hidden, "Show details", "Additional information" closed by default.

### P14: Things clear themselves when they're done.
> "We show what needs the human-in-the-loop action, but we also clear whatever is done."

- **From:** Decision 6 (the sync message).
- **Applied:** row 2 ("No longer in Jamf" clears itself when the laptop comes back); the "Needs attention" button disappears at zero.

### P15: Context everywhere, on demand (the info symbol).
> "Each thing that is shown has the information symbol on it. So if you click it, then it shows what this actually is, what this means. So context is given heavily."

> "Everything that needs attention has an information button where if you click the information button, it tells you what to do or what to check."

- **From:** Decision 6.
- **Applied:** ⓘ next to every status that needs a person, and next to "Jamf sent a value WorkWize couldn't read". An ⓘ on every other field is planned (Tier 3), so this principle is only partly built.

### P16: Never make a gap look like our mistake, and never say something that's only sometimes true.
No single quote: it came from Muhammad's point that "Not reported" "reads like a mistake on our end when it's actually a systemic issue we can't resolve", and his refinement that "Not applicable" is only true for some missing fields.

- **From:** Decision 6 (wording for missing information).
- **Applied:** rows 6 and 18: "Not provided by Jamf" for gaps; a separate label when Jamf did send something WorkWize can't use.

### P17: The context ladder: never leave something blank if context exists.
> "Try not to, in most cases, leave anything blank. If you can give context, then give context. And if you can give further context with the information button, then that's even better. So essential context is priority... It goes in stages, right? If you have the info, you give both levels of info. If you don't have enough, you just give basic essential one. And if you don't have anything, then you give nothing. That's a design decision that we've made."

- **From:** Decision 6 (the employees page).
- **Applied:** "No laptops", "Not provided by Jamf", empty-list texts, ⓘ explanations.

### P18: Take people straight to the problem. Don't make them search.
> "If two need attention, straight click on the link that's given on the dashboard and it redirects you towards whichever page that attention is needed in, rather than you finding where the thing that needs attention is. So that's convenience."

> "We just give them the essential and then if they want more they can navigate their way, and we can help them navigate as well."

- **From:** Decision 6 (the home page).
- **Applied:** every laptop in the sync message's attention list links to its page; the "Needs attention" filter.
