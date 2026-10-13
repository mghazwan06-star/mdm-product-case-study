# Jamf scenario files

Copies of `files/api-mock-response.json` with one change each, for manual checks in the website. The original mock file is never edited.

| File | What's different |
|---|---|
| `emma-leaves.json` | Emma Johnson's MacBook Air (FVFH90ABC123) has no owner in Jamf any more |
| `eventful.json` | Emma Johnson's MacBook Air has no owner any more, and Lucas Brown's Design Team MacBook (FVFUXDESIGN1) is gone from the list |
| `empty.json` | A valid but empty list (0 laptops) |
| `broken.json` | The list is cut off halfway (not valid JSON) |

Start the app with a scenario, press Sync Now, then start it normally again:

```bash
MDM_JAMF_MOCK_PATH=/var/www/backend/tests/Fixtures/jamf/emma-leaves.json docker compose up -d backend
docker compose up -d backend
```
