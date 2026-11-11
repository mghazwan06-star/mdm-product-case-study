"""
Turns the yearly results into the numbers used in docs/RESULTS.md.

    python3 simulation/summarize.py            # writes simulation/results/summary.json
"""

import glob
import json
import os
import statistics

HERE = os.path.dirname(os.path.abspath(__file__))
RESULTS = os.path.join(HERE, "results")


def pct(a, b):
    return round(100 * a / b, 1) if b else None


def one_year(r):
    a = r["assumptions"]
    value = a["laptop_value_eur"]
    weeks = r["weeks"]
    S = a["stress_events"]
    cut, part = S["week_the_export_arrives_cut_off"], S["week_only_part_of_the_list_arrives"]
    normal = [w for w in weeks if w["week"] not in (cut, part)]
    end = weeks[-1]
    ev = r["event_counts"]

    eps = r["leaver_laptops"]
    flagged = [e for e in eps if e["app_flag_week"] is not None]
    same_week = [e for e in flagged if e["app_flag_week"] == e["left_week"]]
    within_1 = [e for e in flagged if e["app_flag_week"] - e["left_week"] <= 1]

    def avg_right(system, rows):
        return round(statistics.mean(100 * w[system]["right"] / max(1, w[system]["right"] + w[system]["wrong"]) for w in rows), 1)

    def to_be_collected_flags(w):
        return w["app"]["leaver_flagged"] + w["app"]["back_with_it_still_flagged"]

    stale = [w["app"]["back_with_it_still_flagged"] for w in normal]
    tbc = [to_be_collected_flags(w) for w in normal]
    pw = next(w for w in weeks if w["week"] == part)
    after = next(w for w in weeks if w["week"] == part + 1)
    cw = next(w for w in weeks if w["week"] == cut)

    lost = ev.get("lost", 0) + ev.get("written_off", 0)
    return {
        "seed": r["seed"],
        "employees": a["employees"],
        "people_who_left": ev.get("person_left", 0),
        "leaver_laptops_out_at_a_sync": len(eps),
        "leaver_laptops_flagged_by_app": len(flagged),
        "leaver_flagged_same_week_pct": pct(len(same_week), len(eps)),
        "leaver_flagged_within_a_week_pct": pct(len(within_1), len(eps)),
        "leaver_flagged_by_simple_sync": 0,
        "leaver_hardware_flagged_eur": len(flagged) * value,
        "never_returned_laptops": sum(1 for e in eps if e["never_returned"]),
        "never_returned_flagged_by_app": sum(1 for e in eps if e["never_returned"] and e["app_flag_week"] is not None),
        "lost_or_written_off": lost,
        "lost_or_written_off_eur": lost * value,
        "retired": ev.get("retired", 0),
        "gone_at_year_end": end["app"]["gone"],
        "gone_flagged_by_app_at_year_end": end["app"]["gone_flagged"],
        "gone_shown_as_assigned_by_simple_sync": end["simple"]["gone_shown_assigned"],
        "records_right_avg_pct_app": avg_right("app", normal),
        "records_right_avg_pct_simple": avg_right("simple", normal),
        "records_right_year_end_pct_app": pct(end["app"]["right"], end["app"]["right"] + end["app"]["wrong"]),
        "records_right_year_end_pct_simple": pct(end["simple"]["right"], end["simple"]["right"] + end["simple"]["wrong"]),
        "simple_records_wrong_year_end": end["simple"]["wrong"],
        "app_records_wrong_year_end": end["app"]["wrong"],
        "duplicate_records_year_end_simple": end["simple"]["extra_records"],
        "duplicate_records_year_end_app": end["app"]["extra_records"],
        "serial_format_changes": ev.get("serial_format_changed", 0),
        "cut_off_week_app_changed_nothing": cw["app_unchanged"],
        "cut_off_week_app_answer": cw["app_status"],
        "partial_week_laptops_missing_from_list": pw["app"]["jamf_gap"],
        "partial_week_false_flags_app": pw["app"]["jamf_gap_flagged"],
        "partial_week_flags_left_next_week": after["app"]["jamf_gap_flagged"],
        "stale_flags_back_with_it_avg_per_week": round(statistics.mean(stale), 1),
        "stale_share_of_to_be_collected_pct": pct(sum(stale), sum(tbc)),
        "one_week_dropouts": ev.get("dropout", 0),
        "records_lost_or_deleted_app": 0 if all(w["app"]["records"] >= weeks[i - 1]["app"]["records"] for i, w in enumerate(weeks) if i) else "check",
        "sync_seconds_avg": round(statistics.mean(w["app_seconds"] for w in weeks), 2),
        "sync_seconds_max": max(w["app_seconds"] for w in weeks),
        "records_at_year_end_app": end["app"]["records"],
    }


def spread(rows, key):
    vals = [r[key] for r in rows if isinstance(r[key], (int, float)) and not isinstance(r[key], bool)]
    if not vals:
        return [r[key] for r in rows]
    return {"median": statistics.median(vals), "min": min(vals), "max": max(vals)}


def main():
    out = {}
    for d in sorted(glob.glob(os.path.join(RESULTS, "*-employees"))):
        rows = [one_year(json.load(open(f))) for f in sorted(glob.glob(os.path.join(d, "seed-*.json")))]
        out[os.path.basename(d)] = {"years": len(rows), "per_year": rows,
                                    "summary": {k: spread(rows, k) for k in rows[0] if k != "seed"}}
    with open(os.path.join(RESULTS, "summary.json"), "w") as f:
        json.dump(out, f, indent=1)
    for name, block in out.items():
        print(f"\n== {name} ({block['years']} years)")
        for k, v in block["summary"].items():
            print(f"  {k}: {v}")


if __name__ == "__main__":
    main()
