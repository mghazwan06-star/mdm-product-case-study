// AI test users: each persona tries each task in the real app, in a real browser.
// The tester only sees what a person sees (the page text and what can be clicked)
// and decides one step at a time. A separate judge checks the final answer against
// what is actually true. See README.md.
//
//   node run.mjs [--only persona:task] [--repeat 1] [--out results/<name>]

import { chromium } from 'playwright'
import { execFileSync, spawn } from 'node:child_process'
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const HERE = path.dirname(fileURLToPath(import.meta.url))
const ROOT = path.dirname(HERE)
const APP = process.env.APP_URL ?? 'http://localhost:5173'
const API = process.env.API_URL ?? 'http://127.0.0.1:8000/api'
const PROJECT = process.env.SIM_PROJECT ?? 'mdm-portfolio-sim'
const MODEL = process.env.TESTER_MODEL ?? 'sonnet'
const JAMF_FILE = path.join(ROOT, 'backend/storage/app/simulation/jamf-this-week.json')
const MAX_STEPS = 15
const SCRATCH = fs.mkdtempSync(path.join(process.env.TMPDIR ?? '/tmp', 'ai-testers-'))

const args = Object.fromEntries(process.argv.slice(2).reduce((a, x, i, all) =>
  x.startsWith('--') ? [...a, [x.slice(2), all[i + 1]]] : a, []))
const personas = JSON.parse(fs.readFileSync(path.join(HERE, 'personas.json')))
const tasks = JSON.parse(fs.readFileSync(path.join(HERE, 'tasks.json')))
const out = path.resolve(HERE, args.out ?? 'results/latest')
fs.mkdirSync(path.join(out, 'screens'), { recursive: true })

// ---------- talking to Claude (through the local Claude Code app, no tools) ----------

function claude(system, prompt) {
  return new Promise((resolve, reject) => {
    const p = spawn('claude', ['-p', '--model', MODEL, '--output-format', 'json', '--tools', '',
      '--setting-sources', '', '--strict-mcp-config', '--no-session-persistence', '--system-prompt', system],
      { cwd: SCRATCH })
    let o = ''
    p.stdout.on('data', d => (o += d))
    p.on('close', () => {
      try {
        const text = JSON.parse(o).result
        const json = text.slice(text.indexOf('{'), text.lastIndexOf('}') + 1)
        resolve(JSON.parse(json))
      } catch (e) { reject(new Error('Unreadable reply: ' + o.slice(0, 300))) }
    })
    p.stdin.end(prompt)
  })
}

async function ask(system, prompt) {
  for (let i = 0; i < 3; i++) {
    try { return await claude(system, prompt) } catch (e) { if (i === 2) throw e }
  }
}

// ---------- the app ----------

const fixture = name => fs.readFileSync(name === 'api-mock-response.json'
  ? path.join(ROOT, 'files', name) : path.join(ROOT, 'backend/tests/Fixtures/jamf', name), 'utf8')

async function setUp(setup) {
  execFileSync('docker', ['compose', '-p', PROJECT, 'exec', '-T', 'backend', 'php', 'artisan', 'migrate:fresh', '--force'],
    { cwd: ROOT, stdio: 'ignore' })
  for (const f of [].concat(setup.synced_with)) {
    fs.writeFileSync(JAMF_FILE, fixture(f))
    const r = await fetch(API + '/sync', { method: 'POST', headers: { Accept: 'application/json' } })
    if (!r.ok) throw new Error('Set-up sync failed')
  }
  fs.writeFileSync(JAMF_FILE, fixture(setup.jamf_now))
}

async function checkApi(kind) {
  if (!kind) return null
  if (kind === 'alex_deleted') {
    const people = (await (await fetch(API + '/employees', { headers: { Accept: 'application/json' } })).json()).data
    return { alex_deleted: !people.some(p => p.name === 'Alex Smith') }
  }
  if (kind === 'synced') {
    const assets = (await (await fetch(API + '/assets', { headers: { Accept: 'application/json' } })).json()).data
    return { synced: assets.some(a => a.status === 'to_be_collected') }
  }
}

// What the tester can see: the page's text and a numbered list of things to click or type in.
async function look(page) {
  const items = await page.evaluate(() => {
    const els = [...document.querySelectorAll('a, button, input, select, textarea, summary, [role=button], [tabindex="0"]')]
    let n = 0
    return els.filter(e => {
      const r = e.getBoundingClientRect(), s = getComputedStyle(e)
      return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none'
    }).map(e => {
      e.setAttribute('data-tester', ++n)
      const label = (e.getAttribute('aria-label') || e.innerText || e.value || e.getAttribute('title') || e.getAttribute('placeholder') || '').trim().replace(/\s+/g, ' ').slice(0, 90)
      return `[${n}] ${e.tagName.toLowerCase()}${e.type && e.tagName === 'INPUT' ? ':' + e.type : ''} "${label}"`
    })
  })
  const text = (await page.evaluate(() => document.body.innerText)).replace(/\n{3,}/g, '\n\n').slice(0, 5000)
  return { url: page.url().replace(APP, ''), text, items }
}

// ---------- one tester, one task ----------

async function attempt(browser, persona, task, run) {
  await setUp(task.setup)
  const phone = persona.viewport === 'phone'
  const ctx = await browser.newContext(phone
    ? { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 }
    : { viewport: { width: 1280, height: 800 } })
  const page = await ctx.newPage()
  const system = `You are ${persona.name}. ${persona.who}
You are using a web app for the first time, on a ${phone ? 'phone' : 'laptop'}. Behave like this person would, including their impatience or caution.
Each turn you see the page text and a numbered list of things you can click or type in. Reply with ONLY a JSON object, no other text:
{"thinking": "<one short sentence, in your own voice>", "action": "click" | "type" | "back" | "answer" | "give_up", "id": <number for click/type>, "text": "<text for type>", "answer": "<for answer: your full answer to the task>", "feedback": "<for answer or give_up: one or two sentences on what was easy or confusing>"}
Only use what you see on the screen. Don't guess facts you haven't seen.`
  const history = []
  const dialogs = []
  let pendingDialog = null
  page.on('dialog', async d => {
    pendingDialog = d.message()
    const r = await ask(system, `A confirmation box popped up:\n"${d.message()}"\n\nYour task: ${task.task}\nReply with JSON: {"thinking": "...", "accept": true|false}`)
    dialogs.push({ message: d.message(), accepted: r.accept, thinking: r.thinking })
    pendingDialog = null
    r.accept ? await d.accept() : await d.dismiss()
  })
  await page.goto(APP)
  const t0 = Date.now()
  let result = { outcome: 'ran_out_of_steps' }
  for (let step = 1; step <= MAX_STEPS; step++) {
    await page.waitForTimeout(800)
    const seen = await look(page)
    const prompt = `Your task: ${task.task}

Steps so far:
${history.map(h => `${h.step}. ${h.action}${h.target ? ' ' + h.target : ''} (${h.thinking})`).join('\n') || '(none yet)'}

Page: ${seen.url}
--- what the page says ---
${seen.text}
--- things you can click or type in ---
${seen.items.join('\n')}

Step ${step} of at most ${MAX_STEPS}. What do you do?`
    const a = await ask(system, prompt)
    const target = a.id ? seen.items[a.id - 1] : null
    history.push({ step, url: seen.url, action: a.action, target, text: a.text, thinking: a.thinking })
    if (a.action === 'answer' || a.action === 'give_up') {
      result = { outcome: a.action, answer: a.answer ?? '', feedback: a.feedback ?? '' }
      break
    }
    try {
      const el = page.locator(`[data-tester="${a.id}"]`)
      if (a.action === 'click') await el.click({ timeout: 5000 })
      else if (a.action === 'type') await el.fill(a.text ?? '', { timeout: 5000 })
      else if (a.action === 'back') await page.goBack()
      await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {})
      while (pendingDialog) await page.waitForTimeout(300)
    } catch (e) {
      history[history.length - 1].error = e.message.split('\n')[0]
    }
  }
  const shot = `${persona.id}--${task.id}--${run}.jpg`
  await page.screenshot({ path: path.join(out, 'screens', shot), fullPage: true, type: 'jpeg', quality: 70 })
  await ctx.close()
  return { persona: persona.id, task: task.id, run, steps: history.length, seconds: Math.round((Date.now() - t0) / 1000),
    history, dialogs, ...result, api: await checkApi(task.check_api), screenshot: 'screens/' + shot }
}

// ---------- the judge ----------

async function judge(task, r) {
  if (r.outcome !== 'answer') return { verdict: 'fail', facts: [], reason: r.outcome }
  const verdict = await ask('You are a strict, fair examiner. Reply with ONLY JSON.',
    `A tester was given this task in a laptop-tracking app:
"${task.task}"

The facts a complete answer must contain:
${task.must.map((m, i) => `${i + 1}. ${m}`).join('\n')}

The tester's answer:
"${r.answer}"
${r.api ? `\nWhat the app's data shows afterwards: ${JSON.stringify(r.api)}` : ''}

Also true (the app shows or says this, so repeating it is correct, not invented):
${(task.also_true ?? []).map(x => '- ' + x).join('\n')}

For each fact, decide if the answer states it (same meaning, any wording) or if the data shows it was done. Reply:
{"facts": [true|false, ...], "wrong_claims": "<anything in the answer that is false or invented, or empty>"}`)
  const hit = verdict.facts.filter(Boolean).length
  const wrong = (verdict.wrong_claims ?? '').trim()
  verdict.wrong_claims = /^(none\.?|n\/a|-)?$/i.test(wrong) ? '' : wrong
  const all = hit === task.must.length && !verdict.wrong_claims
  return { verdict: all ? 'pass' : hit > 0 ? 'partial' : 'fail', facts: verdict.facts, wrong_claims: verdict.wrong_claims }
}

// ---------- run everything ----------

// Re-score saved answers with the current judge (keeps the earlier verdict as judge_v1).
if (args.rejudge) {
  const file = path.resolve(HERE, args.rejudge, 'runs.json')
  const saved = JSON.parse(fs.readFileSync(file))
  for (const r of saved) {
    r.judge_v1 ??= { verdict: r.verdict, facts: r.facts, wrong_claims: r.wrong_claims }
    Object.assign(r, await judge(tasks.find(t => t.id === r.task), r))
    console.log(`${r.persona.padEnd(12)} ${r.task.padEnd(14)} ${r.judge_v1.verdict} -> ${r.verdict}  ${r.wrong_claims ? 'WRONG: ' + r.wrong_claims.slice(0, 90) : ''}`)
  }
  fs.writeFileSync(file, JSON.stringify(saved, null, 1))
  process.exit(0)
}

const only = args.only?.split(':')
const repeat = Number(args.repeat ?? 1)
const browser = await chromium.launch()
const results = []
for (const persona of personas) {
  for (const task of tasks) {
    if (only && (only[0] !== persona.id && only[0] !== '*' || only[1] && only[1] !== task.id)) continue
    for (let run = 1; run <= repeat; run++) {
      const r = await attempt(browser, persona, task, run)
      Object.assign(r, await judge(task, r))
      results.push(r)
      fs.writeFileSync(path.join(out, 'runs.json'), JSON.stringify(results, null, 1))
      console.log(`${persona.id.padEnd(12)} ${task.id.padEnd(14)} ${r.verdict.padEnd(8)} ${String(r.steps).padStart(2)} steps ${String(r.seconds).padStart(4)}s  ${r.wrong_claims ? 'WRONG: ' + r.wrong_claims.slice(0, 80) : ''}`)
    }
  }
}
await browser.close()
