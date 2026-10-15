import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

// F16: the test browser can't measure layout, so this checks the style rules that make
// very long text wrap. The effect itself was measured in a real browser (TEST-LOG, F16).
const css = readFileSync(resolve(process.cwd(), 'src/App.css'), 'utf8')

function rule(selector) {
  const match = css.match(new RegExp(`(^|\\n)${selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*\\{([^}]*)\\}`))
  return match ? match[2] : ''
}

describe('very long text (F16)', () => {
  it('lets a status label with a very long name wrap instead of running off the page', () => {
    expect(rule('.status')).not.toMatch(/white-space:\s*nowrap/)
    expect(rule('.status')).toMatch(/overflow-wrap:\s*anywhere/)
    expect(rule('.status-wrap')).toMatch(/min-width:\s*0/)
  })

  it('shows the explanation next to "couldn\'t read" in the normal ⓘ style', () => {
    expect(rule('.unreadable .info-text')).toMatch(/font-style:\s*normal/)
  })
})
