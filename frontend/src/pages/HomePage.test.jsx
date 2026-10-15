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

  it('F11: a page whose data fails with an error says something went wrong, not "couldn\'t reach"', async () => {
    mockApi({ 'GET /api/assets': [500, undefined], 'GET /api/employees': { data: [] } })
    renderHome()
    expect(await screen.findByRole('alert')).toHaveTextContent('Something went wrong in WorkWize. Try again in a minute.')
  })

  it('F2/F12: a technical error page is never shown, even if it has a message', async () => {
    const debugPage = { message: 'SQLSTATE[HY000] [2002] Connection refused (Host: mysql, Port: 3306)', exception: 'QueryException', trace: [] }
    mockApi({ 'GET /api/assets': [500, debugPage], 'GET /api/employees': { data: [] } })
    renderHome()
    const alert = await screen.findByRole('alert')
    expect(alert).toHaveTextContent('Something went wrong in WorkWize. Try again in a minute.')
    expect(alert).not.toHaveTextContent('SQLSTATE')
  })
})
