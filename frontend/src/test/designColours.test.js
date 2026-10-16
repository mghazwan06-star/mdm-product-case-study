import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'
import { statusTone } from '../lib/labels'

// The colours the browser actually uses, read from the stylesheets: every status badge and
// every button must pass WCAG AA (4.5:1), and the four statuses must look different.
const tokensCss = readFileSync(resolve(process.cwd(), 'src/index.css'), 'utf8')
const appCss = readFileSync(resolve(process.cwd(), 'src/App.css'), 'utf8')
const tokens = Object.fromEntries([...tokensCss.matchAll(/--([\w-]+):\s*(#[0-9a-f]{6})/gi)].map(([, name, hex]) => [name, hex]))

// The declarations of one exact selector, e.g. rule('.status-ok') → { color: '#17663f', background: '#e3f4e9' }
function rule(selector) {
  const escaped = selector.replace(/[.:()[\]'=]/g, '\\$&')
  const match = appCss.match(new RegExp(`(^|\\n)${escaped}\\s*\\{([^}]*)\\}`))
  if (!match) return null
  const value = (prop) => {
    const found = match[2].match(new RegExp(`(?:^|[;\\s])${prop}:\\s*([^;]+);`))
    if (!found) return undefined
    const raw = found[1].trim()
    const token = raw.match(/^var\(--([\w-]+)\)$/)
    return token ? tokens[token[1]] : raw
  }
  return { color: value('color'), background: value('background') }
}

function luminance(hex) {
  const [r, g, b] = [1, 3, 5].map((i) => {
    const c = parseInt(hex.slice(i, i + 2), 16) / 255
    return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4
  })
  return 0.2126 * r + 0.7152 * g + 0.0722 * b
}

function contrast(a, b) {
  const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x)
  return (light + 0.05) / (dark + 0.05)
}

const STATUSES = ['assigned', 'to_be_collected', 'missing_from_mdm', 'owner_removed_locally']

// The colours a status label gets: its own status rule if there is one, otherwise its tone's rule.
function statusColours(status) {
  const own = rule(`.status-is-${status}`)
  const tone = rule(`.status-${statusTone(status)}`)
  return { color: own?.color ?? tone?.color, background: own?.background ?? tone?.background }
}

describe('status badges', () => {
  it.each(STATUSES)('%s has readable text on its badge (WCAG AA)', (status) => {
    const { color, background } = statusColours(status)
    expect(color).toMatch(/^#[0-9a-f]{6}$/i)
    expect(background).toMatch(/^#[0-9a-f]{6}$/i)
    expect(contrast(color, background)).toBeGreaterThanOrEqual(4.5)
  })

  it('gives each of the four statuses its own colours', () => {
    const pairs = STATUSES.map((status) => {
      const { color, background } = statusColours(status)
      return `${color}/${background}`.toLowerCase()
    })
    expect(new Set(pairs).size).toBe(4)
  })
})

describe('buttons', () => {
  const white = '#ffffff'
  const cases = [
    ['Sync Now and other main buttons', rule('.button-primary')],
    ['secondary buttons (Delete in the lists, Cancel)', rule('.button')],
    ['the red Delete in the confirmation', rule('.button-danger')],
  ]

  it.each(cases)('%s are readable (WCAG AA)', (_, colours) => {
    expect(contrast(colours.color === 'var(--accent-text)' ? white : colours.color ?? white, colours.background)).toBeGreaterThanOrEqual(4.5)
  })

  it('stays readable on hover', () => {
    expect(contrast(white, rule('.button-primary:hover:not(:disabled)').background)).toBeGreaterThanOrEqual(4.5)
    expect(contrast(white, rule('.button-danger:hover:not(:disabled)').background)).toBeGreaterThanOrEqual(4.5)
  })
})
