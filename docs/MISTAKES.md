# Mistakes and lessons

Things that went wrong, how we found them, and what we changed.

<!-- Format:
## Short title
- What happened:
- How we noticed:
- Fix:
- Lesson:
-->

## Safety check silently replaced by Laravel's own method
- What happened: I put the "only use the test database" check in a method that Laravel's database test helper also defines. PHP stopped with an error, and had that been "fixed" the obvious way, Laravel's empty version would have quietly replaced our check.
- How we noticed: the first test run crashed before doing anything.
- Fix: moved the check to an earlier setup step that Laravel's helper doesn't override, then proved it by pointing the tests at the real database on purpose: they were refused.
- Lesson: a safety net only counts once you've seen it catch something.

## Tests still pointed at the real database
- What happened: the test settings said "use workwize_test", but Docker's setting for the real database won, because PHP reads settings from two places and the override only covered one.
- How we noticed: the safety check above stopped every test with "Tests must run against workwize_test, not workwize". Nothing was wiped.
- Fix: set the test database in both places in `phpunit.xml`.
- Lesson: check where a setting actually comes from, not just where you set it.

