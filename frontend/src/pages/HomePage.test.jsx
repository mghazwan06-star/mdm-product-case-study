import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router'
import { describe, expect, it, vi } from 'vitest'
import { UNREACHABLE } from '../api'
import HomePage from './HomePage'

function mockApi(responses) {
  vi.spyOn(globalThis, 'fetch').mockImplementation(async (url) => {
    const body = responses[url]
    if (body instanceof Error) throw body
    return new Response(JSON.stringify(body), { status: 200, headers: { 'Content-Type': 'application/json' } })
  })
}

function renderHome() {
  return render(<MemoryRouter><HomePage /></MemoryRouter>)
}

describe('HomePage', () => {
  it('shows the empty state before the first sync', async () => {
    mockApi({ '/api/assets': { data: [] }, '/api/employees': { data: [] } })
    renderHome()
    expect(await screen.findByText('No data yet. Press Sync Now to import laptops from Jamf.')).toBeInTheDocument()
  })

  it('shows totals with real singular and plural', async () => {
    mockApi({ '/api/assets': { data: new Array(7).fill({}) }, '/api/employees': { data: [{}] } })
    renderHome()
    expect(await screen.findByRole('link', { name: '7 laptops' })).toHaveAttribute('href', '/assets')
    expect(screen.getByRole('link', { name: '1 person' })).toHaveAttribute('href', '/employees')
  })

  it('shows a plain message when the backend is unreachable', async () => {
    mockApi({ '/api/assets': new TypeError('Failed to fetch'), '/api/employees': new TypeError('Failed to fetch') })
    renderHome()
    expect(await screen.findByRole('alert')).toHaveTextContent(UNREACHABLE)
  })
})
