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

## Docker settings never reached the website
- What happened: since step 4.1, `docker-compose.yml` set the database and Jamf file settings for the backend. Laravel's development server passes only a short list of settings on to web requests, so the website used `backend/.env` instead. It looked fine because both held the same values.
- How we noticed: the click-through. With the "Emma leaves" scenario selected, Sync Now showed "7 unchanged". The command line saw the scenario file; the website didn't.
- Fix: added our settings to the server's pass-through list, with a test that fails if one is missing.
- Lesson: "it works" can be a coincidence. Changing a setting and watching the result is a better check than seeing it work with the default.

## Tests used whatever Jamf file the app was set to
- What happened: the tests read the Jamf file from the app's setting, so while the app ran a scenario, 26 tests ran against the scenario instead of the real mock.
- How we noticed: the full test run during the fix above failed in 26 places at once.
- Fix: the test settings now always point at the real mock file.
- Lesson: tests must control their own inputs, not inherit them from the running app.

