import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router'
import { describe, expect, it } from 'vitest'
import { SyncProvider } from '../components/SyncContext'
import { alexPro, emmaAir, mockApi } from '../test/api'
import AssetsPage from './AssetsPage'

function renderAssets() {
  return render(<MemoryRouter><SyncProvider><AssetsPage /></SyncProvider></MemoryRouter>)
}

const syncSuccess = {
  status: 'success',
  counts: { assets_created: 0, assets_updated: 1, assets_unchanged: 6, became_to_be_collected: 1 },
  skipped: [],
}

describe('AssetsPage', () => {
  it('lists laptops with their status labels, as in the wireframe', async () => {
    mockApi({ 'GET /api/assets': { data: [alexPro, emmaAir] } })
    renderAssets()

    expect(screen.getByRole('heading', { name: 'Asset Management' })).toBeInTheDocument()
    expect(await screen.findByRole('link', { name: 'Alex’s MacBook Pro' })).toHaveAttribute('href', '/assets/1')
    expect(screen.getByText('Assigned to Alex Smith')).toBeInTheDocument()
    expect(screen.getByText('To be collected from Emma Johnson')).toBeInTheDocument()
  })

  it('shows the empty state before the first sync', async () => {
    mockApi({ 'GET /api/assets': { data: [] } })
    renderAssets()

    expect(await screen.findByText('No laptops yet. Press Sync Now to import laptops from Jamf.')).toBeInTheDocument()
  })

  it('syncs, shows the message until closed, and reloads the list', async () => {
    let synced = false
    const calls = mockApi({
      'GET /api/assets': () => ({ data: synced ? [alexPro, emmaAir] : [alexPro] }),
      'POST /api/sync': () => {
        synced = true
        return syncSuccess
      },
    })
    renderAssets()
    await screen.findByText('Assigned to Alex Smith')

    await userEvent.click(screen.getByRole('button', { name: 'Sync Now' }))

    const message = await screen.findByRole('status')
    expect(within(message).getByText("⚠ 1 laptop now needs collecting. It's marked in the list below.")).toBeInTheDocument()
    expect(within(message).getByText('✓ Sync complete: 6 unchanged.')).toBeInTheDocument()
    expect(await screen.findByText('To be collected from Emma Johnson')).toBeInTheDocument()
    expect(calls.filter((c) => c === 'GET /api/assets')).toHaveLength(2)

    await userEvent.click(screen.getByRole('button', { name: 'Close message' }))
    expect(screen.queryByText('✓ Sync complete: 6 unchanged.')).not.toBeInTheDocument()
  })

  it("shows the plain failure message when Jamf can't be read", async () => {
    mockApi({
      'GET /api/assets': { data: [alexPro] },
      'POST /api/sync': [503, { status: 'failed', message: "Couldn't read the Jamf list. Nothing was changed. Try again later." }],
    })
    renderAssets()
    await screen.findByText('Assigned to Alex Smith')

    await userEvent.click(screen.getByRole('button', { name: 'Sync Now' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(
      "✕ Couldn't read the Jamf list. Nothing was changed. Try again later.",
    )
  })

  it('asks before deleting, with the wording that matches the status', async () => {
    let deleted = false
    mockApi({
      'GET /api/assets': () => ({ data: deleted ? [alexPro] : [alexPro, emmaAir] }),
      'DELETE /api/assets/2': () => {
        deleted = true
        return { message: 'Emma’s MacBook Air was deleted from WorkWize.', returns_on_next_sync: false }
      },
    })
    renderAssets()

    await userEvent.click(await screen.findByRole('button', { name: 'Delete Alex’s MacBook Pro' }))
    expect(screen.getByRole('dialog')).toHaveTextContent('Jamf still lists it, so it will come back on the next sync.')
    await userEvent.click(screen.getByRole('button', { name: 'Cancel' }))
    expect(screen.getByText('Assigned to Alex Smith')).toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: 'Delete Emma’s MacBook Air' }))
    expect(screen.getByRole('dialog')).toHaveTextContent("won't come back unless Jamf assigns it to someone again")
    await userEvent.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Delete' }))

    expect(await screen.findByText('Emma’s MacBook Air was deleted from WorkWize.')).toBeInTheDocument()
    expect(screen.queryByText('To be collected from Emma Johnson')).not.toBeInTheDocument()
  })
})
