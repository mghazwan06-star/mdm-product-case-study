"""
A made-up company that lives through a year, one week at a time.

It knows the truth about every laptop (who has it, whether it is back with IT,
lost or retired) and writes what Jamf would show each week. Nothing here reads
the app: the app only ever sees the weekly Jamf list, like in real life.
"""

import json
import random
import uuid

FIRST = ["Emma", "Liam", "Olivia", "Noah", "Ava", "Lucas", "Mia", "Daan", "Sophie", "Sem",
         "Julia", "Finn", "Lotte", "Levi", "Anna", "Milan", "Sara", "Jesse", "Eva", "Bram",
         "Zoe", "Ruben", "Nora", "Thijs", "Lisa", "Omar", "Yara", "Adam", "Lina", "Max",
         "Fatima", "Jonas", "Ines", "Hugo", "Elif", "Tom", "Amira", "Luca", "Noor", "Sam"]
LAST = ["de Vries", "Jansen", "Bakker", "Visser", "Smit", "Meijer", "Mulder", "Bos", "Vos",
        "Peters", "Hendriks", "Dekker", "Brouwer", "Dijkstra", "Smith", "Brown", "Wilson",
        "Garcia", "Muller", "Rossi", "Novak", "Kaya", "Haddad", "Silva", "Martin", "Kowalski",
        "Nielsen", "Laurent", "Costa", "Fischer"]
ROLES = ["Backend Engineer", "Product Designer", "Account Executive", "Customer Support",
         "Finance Manager", "Data Analyst", "Operations Lead", "Recruiter", "QA Engineer",
         "Marketing Manager", "Frontend Engineer", "Sales Development Rep"]
MODELS = [("MacBook Air (13-inch, M2, 2022)", "Mac14,2"), ("MacBook Pro (14-inch, M3, 2023)", "Mac15,3"),
          ("MacBook Air (15-inch, M3, 2024)", "Mac15,13"), ("MacBook Pro (16-inch, M1 Pro, 2021)", "MacBookPro18,1"),
          ("MacBook Pro (13-inch, 2020)", "MacBookPro16,2")]

WEEKS_PER_YEAR = 52


def weekly(yearly):
    """A yearly rate as the chance of it happening in one week."""
    return 1 - (1 - yearly) ** (1 / WEEKS_PER_YEAR)


class World:
    def __init__(self, a, seed):
        self.a = a
        self.rng = random.Random(seed)
        self.week = 0
        self.people = {}   # id -> {name, email, username, role, active, left_week}
        self.laptops = {}  # id -> see _new_laptop
        self.events = []   # (week, kind, laptop_id or person_id) for the record
        self._serials = set()
        self._emails = set()
        self._names = set()
        self._next_person = 1
        self._next_laptop = 1
        self._start()

    # ---------- set-up ----------

    def _new_person(self):
        # Names are unique (a middle initial when needed), so a record can be checked by name.
        first, last = self.rng.choice(FIRST), self.rng.choice(LAST)
        name, n = f"{first} {last}", 0
        while name in self._names:
            name = f"{first} {'ABCDEFGHJKLMNPRSTVW'[n % 19]}{'.' + str(n // 19) if n >= 19 else '.'} {last}"
            n += 1
        self._names.add(name)
        email = name.lower().replace(" ", ".").replace("..", ".") + "@company.test"
        self._emails.add(email)
        pid = self._next_person
        self._next_person += 1
        self.people[pid] = dict(name=name, email=email, username=email.split("@")[0],
                                role=self.rng.choice(ROLES), active=True, left_week=None)
        return pid

    def _serial(self):
        while True:
            s = "".join(self.rng.choice("ABCDEFGHJKLMNPQRSTUVWXYZ0123456789") for _ in range(12))
            if s not in self._serials:
                self._serials.add(s)
                return s

    def _new_laptop(self, owner, age_weeks=0):
        lid = self._next_laptop
        self._next_laptop += 1
        model, ident = self.rng.choice(MODELS)
        self.laptops[lid] = dict(
            serial=self._serial(),   # the true serial
            jamf_serial=None,        # set when Jamf shows it in another format (re-enrolment)
            model=model, ident=ident, jamf_id=str(1000 + lid), udid=str(uuid.UUID(int=self.rng.getrandbits(128))),
            bought_week=self.week - age_weeks,
            owner=owner,             # person id or None
            last_owner=owner,
            truth="assigned" if owner else "with_it",
            in_jamf=True,
            since=self.week,         # when the current truth started
            never_returns=False,
            dropout_until=None,
        )
        return lid

    def _start(self):
        a = self.a
        for _ in range(a["employees"]):
            pid = self._new_person()
            self._new_laptop(pid, age_weeks=self.rng.randint(0, 4 * WEEKS_PER_YEAR))
            if self.rng.random() < a["people"]["share_with_a_second_laptop"]:
                self._new_laptop(pid, age_weeks=self.rng.randint(0, 4 * WEEKS_PER_YEAR))
        for _ in range(round(a["employees"] * a["laptops"]["spare_laptops_with_it_at_start"])):
            self._new_laptop(None, age_weeks=self.rng.randint(0, 2 * WEEKS_PER_YEAR))

    # ---------- helpers ----------

    def _set(self, lid, truth, owner="keep"):
        lap = self.laptops[lid]
        lap["truth"] = truth
        lap["since"] = self.week
        if owner != "keep":
            lap["owner"] = owner
            if owner is not None:
                lap["last_owner"] = owner

    def _laptops_of(self, pid):
        return [lid for lid, l in self.laptops.items() if l["owner"] == pid and l["truth"] == "assigned"]

    def _active(self):
        return [pid for pid, p in self.people.items() if p["active"]]

    def _log(self, kind, ref):
        self.events.append((self.week, kind, ref))

    # ---------- one week ----------

    def step(self):
        """Move the company on by one week."""
        self.week += 1
        a, L, rng = self.a, self.a["laptops"], self.rng

        # Laptops that dropped out of Jamf for a week come back.
        for lid, lap in self.laptops.items():
            if lap["dropout_until"] is not None and lap["dropout_until"] <= self.week:
                lap["dropout_until"] = None

        # People leave. IT clears them as owner in Jamf; the laptop is still out there.
        for pid in self._active():
            if rng.random() < weekly(a["people"]["yearly_leaver_rate"]):
                self.people[pid].update(active=False, left_week=self.week)
                self._log("person_left", pid)
                for lid in self._laptops_of(pid):
                    self._set(lid, "awaiting_return", owner=None)
                    self.laptops[lid]["never_returns"] = rng.random() < L["share_of_leaver_laptops_never_returned"]
                    self._log("leaver_laptop_out", lid)

        # Leaver laptops come back to IT, or are written off after a while.
        for lid, lap in list(self.laptops.items()):
            if lap["truth"] != "awaiting_return":
                continue
            waited = self.week - lap["since"]
            if lap["never_returns"]:
                if waited >= L["weeks_before_it_writes_off_a_laptop_that_never_came_back"]:
                    self._set(lid, "written_off")
                    lap["in_jamf"] = False
                    self._log("written_off", lid)
            elif rng.random() < L["weekly_chance_a_leaver_laptop_comes_back"]:
                self._set(lid, "with_it")
                self._log("returned", lid)

        # Old laptops: refreshed (owner gets a new one, old one goes to IT), then retired.
        max_age = L["max_age_years_before_refresh"] * WEEKS_PER_YEAR
        for lid, lap in list(self.laptops.items()):
            age = self.week - lap["bought_week"]
            if lap["truth"] == "assigned" and age > max_age and rng.random() < L["weekly_chance_an_old_laptop_is_refreshed"]:
                owner = lap["owner"]
                self._set(lid, "with_it", owner=None)
                lap["retire_at"] = self.week + L["weeks_a_returned_old_laptop_waits_before_it_is_retired"]
                self._new_laptop(owner)
                self._log("refreshed", lid)
            elif lap["truth"] == "with_it" and lap.get("retire_at") is None and age > max_age:
                lap["retire_at"] = self.week + L["weeks_a_returned_old_laptop_waits_before_it_is_retired"]
            if lap["truth"] == "with_it" and lap.get("retire_at") is not None and self.week >= lap["retire_at"]:
                self._set(lid, "retired")
                lap["in_jamf"] = False
                self._log("retired", lid)

        # New people join and get a spare laptop from IT, or a new one.
        target = a["employees"] * (1 + a["people"]["yearly_headcount_growth"] * self.week / WEEKS_PER_YEAR)
        while len(self._active()) < round(target):
            pid = self._new_person()
            self._log("person_joined", pid)
            spare = [lid for lid, l in self.laptops.items()
                     if l["truth"] == "with_it" and l.get("retire_at") is None and l["since"] < self.week]
            if spare:
                lid = rng.choice(spare)
                self._set(lid, "assigned", owner=pid)
                self._log("reissued", lid)
            else:
                self._new_laptop(pid)
                self._log("bought", pid)

        # Lost or stolen: IT removes it from Jamf.
        for lid, lap in list(self.laptops.items()):
            if lap["truth"] == "assigned" and rng.random() < weekly(L["yearly_lost_or_stolen_rate"]):
                self._set(lid, "lost")
                lap["in_jamf"] = False
                self._log("lost", lid)

        # Handovers: a laptop moves to another active person.
        active = self._active()
        for lid, lap in list(self.laptops.items()):
            if lap["truth"] == "assigned" and rng.random() < weekly(L["yearly_handover_rate"]):
                new = rng.choice(active)
                if new != lap["owner"]:
                    self._set(lid, "assigned", owner=new)
                    self._log("handover", lid)

        # Jamf data quirks.
        J = a["jamf_data"]
        for lid, lap in self.laptops.items():
            if not lap["in_jamf"]:
                continue
            if lap["dropout_until"] is None and rng.random() < J["weekly_chance_a_laptop_drops_out_for_one_week"]:
                lap["dropout_until"] = self.week + 1
                self._log("dropout", lid)
            if lap["jamf_serial"] is None and rng.random() < weekly(J["yearly_chance_a_serial_is_re_enrolled_in_a_different_format"]):
                s = lap["serial"]
                lap["jamf_serial"] = rng.choice([s[:3] + " " + s[3:], s.lower(), " " + s])
                self._log("serial_format_changed", lid)

    # ---------- what Jamf shows ----------

    def jamf_entries(self):
        out = []
        for lid, lap in self.laptops.items():
            if not lap["in_jamf"] or lap["dropout_until"] is not None:
                continue
            p = self.people.get(lap["owner"]) if lap["owner"] else None
            out.append({
                "id": lap["jamf_id"],
                "udid": lap["udid"],
                "general": {"name": (f"{p['name'].split()[0]}'s {lap['model'].split(' (')[0]}" if p else lap["model"].split(" (")[0]),
                            "platform": "Mac"},
                "purchasing": {"purchased": True},
                "userAndLocation": {
                    "username": p["username"] if p else None,
                    "realname": p["name"] if p else None,
                    "email": p["email"] if p else None,
                    "position": p["role"] if p else None,
                    "phone": None,
                },
                "hardware": {"make": "Apple", "model": lap["model"], "modelIdentifier": lap["ident"],
                             "serialNumber": lap["jamf_serial"] or lap["serial"]},
            })
        return out

    def jamf_export(self):
        """The Jamf list for this week, as text, including the stress weeks."""
        S = self.a["stress_events"]
        entries = self.jamf_entries()
        if self.week == S["week_only_part_of_the_list_arrives"]:
            keep = round(len(entries) * S["share_of_the_list_that_arrives_that_week"])
            entries = entries[:keep]
            self._log("partial_list", keep)
        text = json.dumps({"totalCount": len(entries), "results": entries})
        if self.week == S["week_the_export_arrives_cut_off"]:
            text = text[: len(text) // 2]   # cut off half way: not readable
            self._log("cut_off_list", None)
        return text
