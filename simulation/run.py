"""
Runs the simulated year against the real app and the simple-sync model, and
scores both against the truth every week.

Usage (from the repo root, with the simulation stack running, see simulation/README.md):
    python3 simulation/run.py --seeds 1-10 --employees 500 --out simulation/results/main
"""

import argparse
import copy
import json
import os
import statistics
import subprocess
import sys
import time
import urllib.request
import urllib.error

sys.path.insert(0, os.path.dirname(__file__))
from world import World          # noqa: E402
from baseline import SimpleSync  # noqa: E402

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
EXPORT_FILE = os.path.join(ROOT, "backend", "storage", "app", "simulation", "jamf-this-week.json")
API = "http://127.0.0.1:8000/api"
PROJECT = os.environ.get("SIM_PROJECT", "mdm-portfolio-sim")
GONE = {"lost", "written_off", "retired"}


def canon(serial):
    return "".join(serial.split()).upper()


# ---------- the real app ----------

def api(method, path):
    req = urllib.request.Request(API + path, method=method, headers={"Accept": "application/json"})
    try:
        with urllib.request.urlopen(req, timeout=120) as r:
            return r.status, json.loads(r.read() or b"{}")
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read() or b"{}")


def reset_app():
    subprocess.run(["docker", "compose", "-p", PROJECT, "exec", "-T", "backend",
                    "php", "artisan", "migrate:fresh", "--force"],
                   cwd=ROOT, check=True, capture_output=True)


def app_sync(export_text):
    os.makedirs(os.path.dirname(EXPORT_FILE), exist_ok=True)
    with open(EXPORT_FILE, "w") as f:
        f.write(export_text)
    t = time.time()
    code, body = api("POST", "/sync")
    return code, body, time.time() - t


def app_view():
    code, body = api("GET", "/assets")
    assert code == 200, body
    return [{"serial": a["serial_code"], "status": a["status"],
             "owner": a["owner"]["name"] if a["owner"] else None,
             "last_owner": a["last_owner_name"]} for a in body["data"]]


# ---------- scoring ----------

def score(world, rows, system, in_list_this_week):
    """Compare what a system shows with the truth, laptop by laptop."""
    by_serial = {}
    for r in rows:
        by_serial.setdefault(canon(r["serial"]), []).append(r)
    name_of = {pid: p["name"] for pid, p in world.people.items()}
    email_of = {pid: p["email"] for pid, p in world.people.items()}

    out = {"right": 0, "wrong": 0, "leaver_laptops": 0, "leaver_flagged": 0,
           "leaver_shown_with_leaver": 0, "gone": 0, "gone_flagged": 0, "gone_shown_assigned": 0,
           "back_with_it": 0, "back_with_it_still_flagged": 0, "jamf_gap": 0, "jamf_gap_flagged": 0,
           "wrong_owner": 0, "records": len(rows),
           "extra_records": sum(len(v) - 1 for v in by_serial.values() if len(v) > 1)}
    flagged_leaver_ids = []

    for lid, lap in world.laptops.items():
        recs = by_serial.get(lap["serial"])
        if not recs:
            continue  # never imported (a spare that never had an owner)
        rec = recs[0] if len(recs) == 1 else max(recs, key=lambda r: r["serial"] == (lap["jamf_serial"] or lap["serial"]))
        truth = lap["truth"]
        if system == "app":
            shows_owner, status, last = rec["owner"], rec["status"], rec["last_owner"]
            owner_ok = lambda pid: shows_owner == name_of.get(pid)          # noqa: E731
            last_ok = lambda pid: last == name_of.get(pid)                  # noqa: E731
        else:
            # The simple sync only ever shows "Assigned to <owner>"; if the serial changed
            # format there are two rows, and the old one keeps saying the old thing.
            status = "assigned"
            emails = {r["owner_email"] for r in recs}
            owner_ok = lambda pid: emails == {email_of.get(pid)}            # noqa: E731
            last_ok = lambda pid: False                                     # noqa: E731

        ok = False
        if truth == "assigned" and lid not in in_list_this_week:
            out["jamf_gap"] += 1
            if status == "missing_from_mdm":
                out["jamf_gap_flagged"] += 1
            ok = status == "assigned" and owner_ok(lap["owner"])
        elif truth == "assigned":
            ok = status == "assigned" and owner_ok(lap["owner"])
            if not ok:
                out["wrong_owner"] += 1
        elif truth == "awaiting_return":
            out["leaver_laptops"] += 1
            ok = status == "to_be_collected" and last_ok(lap["last_owner"])
            if ok:
                out["leaver_flagged"] += 1
                flagged_leaver_ids.append(lid)
            elif status == "assigned":
                out["leaver_shown_with_leaver"] += 1
        elif truth == "with_it":
            out["back_with_it"] += 1
            if status == "to_be_collected":
                out["back_with_it_still_flagged"] += 1
            ok = False  # neither version can know it is back: Jamf shows the same thing
        elif truth in GONE:
            out["gone"] += 1
            ok = status == "missing_from_mdm"
            if ok:
                out["gone_flagged"] += 1
            else:
                out["gone_shown_assigned"] += 1
        out["right" if ok else "wrong"] += 1
    return out, flagged_leaver_ids


# ---------- one year ----------

def run_year(assumptions, seed, use_app=True):
    world = World(assumptions, seed)
    simple = SimpleSync()
    if use_app:
        reset_app()
    weeks = []
    episodes = {}           # (laptop, week its owner left) -> when the app flagged it
    for _ in range(assumptions["weeks"] + 1):
        export = world.jamf_export()
        listed = set()
        try:
            listed = {int(e["id"]) - 1000 for e in json.loads(export)["results"]}
        except ValueError:
            listed = {lid for lid, l in world.laptops.items() if l["in_jamf"] and l["dropout_until"] is None}
        simple_status = simple.sync(export)
        row = {"week": world.week, "simple_sync": simple_status,
               "simple": score(world, simple.view(), "simple", listed)[0]}
        if use_app:
            before = app_view() if world.week > 0 else []
            code, body, secs = app_sync(export)
            after = app_view()
            app_score, flagged = score(world, after, "app", listed)
            flagged = set(flagged)
            for lid, lap in world.laptops.items():
                if lap["truth"] == "awaiting_return":
                    ep = (lid, lap["since"])       # one leaver episode of one laptop
                    episodes.setdefault(ep, {"laptop": lid, "left_week": lap["since"], "app_flag_week": None,
                                             "never_returned": lap["never_returns"]})
                    if lid in flagged and episodes[ep]["app_flag_week"] is None:
                        episodes[ep]["app_flag_week"] = world.week
            row.update(app=app_score, app_http=code, app_status=body.get("status"),
                       app_seconds=round(secs, 2), app_counts=body.get("counts"),
                       app_unchanged=(code != 200 and before == after))
        weeks.append(row)
        if world.week < assumptions["weeks"]:
            world.step()

    # Per-laptop record of every leaver laptop: when it went out and when the app flagged it.
    # Leaver laptops that were back with IT before the next sync never show up here.
    leaver = list(episodes.values())
    counts = {}
    for (_, kind, _) in world.events:
        counts[kind] = counts.get(kind, 0) + 1
    return {"seed": seed, "weeks": weeks, "leaver_laptops": leaver, "event_counts": counts,
            "laptops_at_end": len(world.laptops),
            "people_active_at_end": sum(1 for p in world.people.values() if p["active"])}


def parse_seeds(s):
    if "-" in s:
        a, b = s.split("-")
        return list(range(int(a), int(b) + 1))
    return [int(x) for x in s.split(",")]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--seeds", default="1")
    ap.add_argument("--employees", type=int)
    ap.add_argument("--out", required=True)
    ap.add_argument("--simple-only", action="store_true")
    args = ap.parse_args()
    with open(os.path.join(os.path.dirname(__file__), "assumptions.json")) as f:
        a = json.load(f)
    if args.employees:
        a = copy.deepcopy(a)
        a["employees"] = args.employees
    os.makedirs(args.out, exist_ok=True)
    for seed in parse_seeds(args.seeds):
        t = time.time()
        res = run_year(a, seed, use_app=not args.simple_only)
        res["assumptions"] = a
        with open(os.path.join(args.out, f"seed-{seed:02d}.json"), "w") as f:
            json.dump(res, f, indent=1)
        last = res["weeks"][-1]
        print(f"seed {seed}: {time.time() - t:.0f}s, app right {last.get('app', {}).get('right')}"
              f" / simple right {last['simple']['right']}", flush=True)


if __name__ == "__main__":
    main()
