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

describe('WorkWize branding', () => {
  it('shows the logo in the top bar, with alt text, as a link to Home', () => {
    render(
      <MemoryRouter>
        <Nav />
      </MemoryRouter>,
    )
    const logo = screen.getByRole('img', { name: 'WorkWize' })
    expect(logo).toHaveAttribute('src', '/workwize-logo.png')
    expect(logo.closest('a')).toHaveAttribute('href', '/')
  })

  it('says who the name and logo belong to', () => {
    render(<Footer />)
    expect(screen.getByRole('contentinfo')).toHaveTextContent(
      'Independent case study. The WorkWize name and logo belong to WorkWize and are used only for this application.',
    )
  })

  it('uses the WorkWize navy for text and a readable WorkWize blue for links and buttons (WCAG AA)', () => {
    expect(tokens.text).toBe('#14244c')
    expect(contrast(tokens.text, tokens.card)).toBeGreaterThanOrEqual(4.5)
    expect(contrast(tokens.accent, tokens.card)).toBeGreaterThanOrEqual(4.5)
    expect(contrast(tokens.accent, tokens.bg)).toBeGreaterThanOrEqual(4.5)
    expect(contrast(tokens['accent-text'], tokens.accent)).toBeGreaterThanOrEqual(4.5)
  })

  it('never puts text in the logo blue, which is too light for text (4.1:1 on white)', () => {
    expect(tokens.brand).toBe('#3c78ec')
    expect(contrast(tokens.brand, tokens.card)).toBeLessThan(4.5)
    expect(appCss).not.toMatch(/(^|[;\s{])color:\s*var\(--brand\)/)
  })
})
