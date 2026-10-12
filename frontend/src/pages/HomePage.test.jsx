import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router'
import { describe, expect, it } from 'vitest'
import { UNREACHABLE } from '../api'
import { SyncProvider } from '../components/SyncContext'
import { mockApi } from '../test/api'
import HomePage from './HomePage'

function renderHome() {
  return render(<MemoryRouter><SyncProvider><HomePage /></SyncProvider></MemoryRouter>)
}

describe('HomePage', () => {
  it('shows the empty state before the first sync', async () => {
    mockApi({ 'GET /api/assets': { data: [] }, 'GET /api/employees': { data: [] } })
    renderHome()
    expect(await screen.findByText('No data yet. Press Sync Now to import laptops from Jamf.')).toBeInTheDocument()
  })

  it('shows totals with real singular and plural', async () => {
    mockApi({ 'GET /api/assets': { data: new Array(7).fill({}) }, 'GET /api/employees': { data: [{}] } })
    renderHome()
    expect(await screen.findByRole('link', { name: '7 laptops' })).toHaveAttribute('href', '/assets')
    expect(screen.getByRole('link', { name: '1 person' })).toHaveAttribute('href', '/employees')
  })

  it('shows a plain message when the backend is unreachable', async () => {
    mockApi({ 'GET /api/assets': new TypeError('Failed to fetch'), 'GET /api/employees': new TypeError('Failed to fetch') })
    renderHome()
    expect(await screen.findByRole('alert')).toHaveTextContent(UNREACHABLE)
  })
})
