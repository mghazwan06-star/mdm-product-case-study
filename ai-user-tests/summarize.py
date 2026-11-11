"""
Summarises one round of AI tester runs.

    python3 ai-user-tests/summarize.py ai-user-tests/results/round-1
"""

import json
import os
import statistics
import sys

d = sys.argv[1]
runs = json.load(open(os.path.join(d, "runs.json")))
tasks = {t["id"]: t for t in json.load(open(os.path.join(os.path.dirname(__file__), "tasks.json")))}


def block(rows):
    n = len(rows)
    passed = sum(r["verdict"] == "pass" for r in rows)
    partial = sum(r["verdict"] == "partial" for r in rows)
    facts = sum(sum(r["facts"]) for r in rows if r.get("facts"))
    facts_all = sum(len(tasks[r["task"]]["must"]) for r in rows)
    return {
        "attempts": n,
        "passed": passed,
        "partial": partial,
        "failed": n - passed - partial,
        "pass_rate_pct": round(100 * passed / n, 1),
        "facts_found_pct": round(100 * facts / facts_all, 1),
        "gave_up": sum(r["outcome"] == "give_up" for r in rows),
        "ran_out_of_steps": sum(r["outcome"] == "ran_out_of_steps" for r in rows),
        "wrong_claims": sum(bool(r.get("wrong_claims")) for r in rows),
        "median_steps": statistics.median(r["steps"] for r in rows),
        "median_seconds": statistics.median(r["seconds"] for r in rows),
        "click_errors": sum(1 for r in rows for h in r["history"] if h.get("error")),
    }


summary = {"overall": block(runs),
           "by_task": {t: block([r for r in runs if r["task"] == t]) for t in tasks if any(r["task"] == t for r in runs)},
           "by_persona": {p: block([r for r in runs if r["persona"] == p]) for p in dict.fromkeys(r["persona"] for r in runs)},
           "not_passed": [{"persona": r["persona"], "task": r["task"], "run": r["run"], "verdict": r["verdict"],
                           "facts": r.get("facts"), "wrong_claims": r.get("wrong_claims"), "answer": r.get("answer"),
                           "feedback": r.get("feedback")} for r in runs if r["verdict"] != "pass"],
           "feedback": [{"persona": r["persona"], "task": r["task"], "feedback": r.get("feedback")} for r in runs],
           "dialogs": [{"persona": r["persona"], "task": r["task"], **dl} for r in runs for dl in r["dialogs"]]}
json.dump(summary, open(os.path.join(d, "summary.json"), "w"), indent=1)

print("OVERALL", summary["overall"])
for k in ("by_task", "by_persona"):
    print("\n" + k)
    for name, b in summary[k].items():
        print(f"  {name:14} pass {b['passed']}/{b['attempts']}  partial {b['partial']}  facts {b['facts_found_pct']}%  "
              f"steps {b['median_steps']}  {b['median_seconds']}s  gave up {b['gave_up']}  wrong {b['wrong_claims']}")
print("\nNOT PASSED")
for r in summary["not_passed"]:
    print(" ", r["persona"], r["task"], r["run"], r["verdict"], r["facts"], "|", (r["wrong_claims"] or "")[:150])
