"""
The simple sync: a model of the obvious "just copy the list" approach, used as
the comparison. It is described in our own words from the review of the example
approach (docs/ENGINEERING.md, "improvements over the reference"):

- a laptop with no owner in Jamf is skipped, so the record keeps the old owner (R2);
- a laptop missing from the list is left as it is, never flagged (R3);
- serial numbers are used exactly as sent, so "C02 XK1" and "C02XK1" are two laptops (R9);
- a list that can't be read changes nothing (we assume it stops, the kindest reading).

It never deletes, so it can't lose a record either. That keeps the comparison fair:
the difference is only in what each version shows.
"""

import json


class SimpleSync:
    def __init__(self):
        self.records = {}  # serial exactly as sent -> owner email

    def sync(self, export_text):
        try:
            data = json.loads(export_text)
        except ValueError:
            return "unreadable"
        for entry in data.get("results", []):
            serial = entry["hardware"]["serialNumber"]
            email = entry["userAndLocation"]["email"]
            if not email:
                continue  # R2: no owner -> skipped, the old owner stays
            self.records[serial] = email
        return "ok"

    def view(self):
        """What the screen would show: one row per record, all 'Assigned to ...'."""
        return [{"serial": s, "status": "assigned", "owner_email": e} for s, e in self.records.items()]
