import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

// WCAG AA: normal text needs a contrast ratio of at least 4.5:1.
// Colours are read from the stylesheet, so changing one there re-checks it here.
const css = readFileSync(resolve(process.cwd(), 'src/index.css'), 'utf8')
const colours = Object.fromEntries([...css.matchAll(/--([\w-]+):\s*(#[0-9a-f]{6})/gi)].map(([, name, hex]) => [name, hex]))
colours.white = '#ffffff'

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

// [what it is, text colour, background colour]
const PAIRS = [
  ['Status label: Assigned', 'ok', 'ok-bg'],
  ['Status label: To be collected / No longer in Jamf', 'warn', 'warn-bg'],
  ['Status label: Owner removed in WorkWize', 'muted', 'neutral-bg'],
  ['Message: sync OK', 'text', 'ok-bg'],
  ['Message: needs collecting', 'text', 'warn-bg'],
  ['Message: failed sync or error', 'error', 'error-bg'],
  ['Message: a sync is already running (grey box)', 'text', 'neutral-bg'],
  ['Message close button on grey', 'muted', 'neutral-bg'],
  ['Message close button on green', 'muted', 'ok-bg'],
  ['Message close button on amber', 'muted', 'warn-bg'],
  ['Message close button on red', 'muted', 'error-bg'],
  ['Body text on the page', 'text', 'bg'],
  ['Grey text on white (serials, field labels, "Not provided by Jamf")', 'muted', 'card'],
  ['Grey text on the page (loading, empty states)', 'muted', 'bg'],
  ['Links on white', 'accent', 'card'],
  ['Links on the page', 'accent', 'bg'],
  ['Sync Now button', 'white', 'accent'],
  ['Delete button in the dialog', 'white', 'error'],
]

describe('colour contrast (WCAG AA, 4.5:1)', () => {
  it.each(PAIRS)('%s', (_, text, background) => {
    expect(colours[text], `--${text} missing`).toBeDefined()
    expect(colours[background], `--${background} missing`).toBeDefined()
    expect(contrast(colours[text], colours[background])).toBeGreaterThanOrEqual(4.5)
  })
})
