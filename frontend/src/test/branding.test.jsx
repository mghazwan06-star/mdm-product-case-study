import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router'
import { describe, expect, it } from 'vitest'
import Footer from '../components/Footer'
import Nav from '../components/Nav'

const tokensCss = readFileSync(resolve(process.cwd(), 'src/index.css'), 'utf8')
const appCss = readFileSync(resolve(process.cwd(), 'src/App.css'), 'utf8')
const tokens = Object.fromEntries([...tokensCss.matchAll(/--([\w-]+):\s*(#[0-9a-f]{6})/gi)].map(([, name, hex]) => [name, hex]))

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

describe('Branding', () => {
  it('shows the app name in the top bar as a link to Home, with no WorkWize logo', () => {
    const { container } = render(
      <MemoryRouter>
        <Nav />
      </MemoryRouter>,
    )
    expect(screen.getByRole('link', { name: 'MDM Device Sync' })).toHaveAttribute('href', '/')
    expect(container.querySelector('img')).toBeNull()
  })

  it('says it is a case study, not a WorkWize product', () => {
    render(<Footer />)
    expect(screen.getByRole('contentinfo')).toHaveTextContent(
      'Independent case study, set by WorkWize and shared with their permission. Not a WorkWize product.',
    )
  })

  it('uses navy for text and a readable blue for links and buttons (WCAG AA)', () => {
    expect(tokens.text).toBe('#14244c')
    expect(contrast(tokens.text, tokens.card)).toBeGreaterThanOrEqual(4.5)
    expect(contrast(tokens.accent, tokens.card)).toBeGreaterThanOrEqual(4.5)
    expect(contrast(tokens.accent, tokens.bg)).toBeGreaterThanOrEqual(4.5)
    expect(contrast(tokens['accent-text'], tokens.accent)).toBeGreaterThanOrEqual(4.5)
  })

  it('never puts text in the bright blue, which is too light for text (4.1:1 on white)', () => {
    expect(tokens.brand).toBe('#3c78ec')
    expect(contrast(tokens.brand, tokens.card)).toBeLessThan(4.5)
    expect(appCss).not.toMatch(/(^|[;\s{])color:\s*var\(--brand\)/)
  })
})
