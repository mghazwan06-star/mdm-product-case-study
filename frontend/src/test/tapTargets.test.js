import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

// On phones the ⓘ and Delete buttons in lists keep their look, but their tap area is at least
// 44 × 44 px: an invisible layer centred on the button. The test browser can't measure layout,
// so this checks the rules; the effect was measured in a real browser (TEST-LOG, redesign).
const css = readFileSync(resolve(process.cwd(), 'src/App.css'), 'utf8')

// Every phone-width block (max-width: 600px), joined.
function phoneCss() {
  const blocks = []
  const re = /@media \(max-width: 600px\) \{/g
  while (re.exec(css)) {
    let depth = 1
    let i = re.lastIndex
    while (depth > 0 && i < css.length) {
      if (css[i] === '{') depth++
      if (css[i] === '}') depth--
      i++
    }
    blocks.push(css.slice(re.lastIndex, i - 1))
  }
  return blocks.join('\n')
}

function declarations(block, selector) {
  const found = [...block.matchAll(/([^{}]+)\{([^}]*)\}/g)].filter(([, selectors]) =>
    selectors.split(',').map((s) => s.trim()).includes(selector),
  )
  return found.map(([, , body]) => body).join('\n')
}

describe('tap areas on phones', () => {
  const phone = phoneCss()

  it.each(['.info-button', '.list-row > .button-small'])('%s has a tap area of at least 44 × 44 px', (selector) => {
    const layer = declarations(phone, `${selector}::after`)
    expect(layer).toMatch(/content:\s*''/)
    expect(layer).toMatch(/position:\s*absolute/)
    expect(Number(layer.match(/min-width:\s*(\d+)px/)?.[1])).toBeGreaterThanOrEqual(44)
    expect(Number(layer.match(/min-height:\s*(\d+)px/)?.[1])).toBeGreaterThanOrEqual(44)
    expect(declarations(phone, selector)).toMatch(/position:\s*relative/)
  })
})
