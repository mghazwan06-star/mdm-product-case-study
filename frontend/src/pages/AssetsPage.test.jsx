import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router'
import { describe, expect, it } from 'vitest'
import { SyncProvider } from '../components/SyncContext'
import { alexPro, assetsReply, emmaAir, mockApi } from '../test/api'
import AssetsPage from './AssetsPage'

function renderAssets() {
  return render(<MemoryRouter><SyncProvider><AssetsPage /></SyncProvider></MemoryRouter>)
}

const syncSuccess = {
  status: 'success',
  counts: {
    assets_created: 0,
    assets_updated: 1,
    assets_unchanged: 6,
    employees_created: 0,
    employees_updated: 0,
    employees_unchanged: 6,
    became_to_be_collected: 1,
    became_missing: 0,
    skipped: 0,
  },
  needs_attention: [
    { asset_id: 2, status: 'to_be_collected', device_name: 'Emma’s MacBook Air', person_name: 'Emma Johnson' },
  ],
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

  it('syncs, shows the full box until closed, and reloads the list', async () => {
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

    const box = within(await screen.findByRole('status'))
    expect(box.getByText('⚠ 1 laptop needs attention')).toBeInTheDocument()
    expect(box.getByRole('link', { name: 'Emma’s MacBook Air' })).toHaveAttribute('href', '/assets/2')
    expect(box.getByText('✓ Sync complete. Laptops: 6 unchanged.')).toBeInTheDocument()
    expect(await screen.findByText('To be collected from Emma Johnson')).toBeInTheDocument()
    expect(calls.filter((c) => c === 'GET /api/assets')).toHaveLength(2)

    await userEvent.click(screen.getByRole('button', { name: 'Close message' }))
    expect(screen.queryByText('⚠ 1 laptop needs attention')).not.toBeInTheDocument()
  })

  it('opens "Show details" and the ⓘ explanation in the box', async () => {
    mockApi({ 'GET /api/assets': { data: [alexPro, emmaAir] }, 'POST /api/sync': syncSuccess })
    renderAssets()
    await screen.findByText('Assigned to Alex Smith')
    await userEvent.click(screen.getByRole('button', { name: 'Sync Now' }))
    const box = within(await screen.findByRole('status'))

    expect(box.queryByText('People: 0 new, 0 updated, 6 unchanged.')).not.toBeInTheDocument()
    await userEvent.click(box.getByRole('button', { name: 'Show details' }))
    expect(box.getByText('Laptops: 0 new, 0 updated, 6 unchanged, 1 now to be collected, 0 now no longer in Jamf.')).toBeInTheDocument()
    expect(box.getByText('People: 0 new, 0 updated, 6 unchanged.')).toBeInTheDocument()
    expect(box.getByRole('button', { name: 'Hide details' })).toHaveAttribute('aria-expanded', 'true')

    const info = box.getByRole('button', { name: 'What does this status mean?' })
    await userEvent.click(info)
    expect(info).toHaveAttribute('aria-expanded', 'true')
    expect(box.getByText(/It was last with Emma Johnson. Arrange to collect it./)).toBeInTheDocument()
  })

  it('has an ⓘ explanation next to statuses that need a person, not next to assigned ones', async () => {
    mockApi({ 'GET /api/assets': { data: [alexPro, emmaAir] } })
    renderAssets()
    await screen.findByText('To be collected from Emma Johnson')

    const emmaRow = screen.getByText('To be collected from Emma Johnson').closest('li')
    const alexRow = screen.getByText('Assigned to Alex Smith').closest('li')
    expect(within(alexRow).queryByRole('button', { name: 'What does this status mean?' })).not.toBeInTheDocument()

    await userEvent.click(within(emmaRow).getByRole('button', { name: 'What does this status mean?' }))
    expect(within(emmaRow).getByText(/Jamf no longer lists an owner for this laptop/)).toBeInTheDocument()
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

  it('shows only the latest message: a sync replaces an old delete note, and a delete replaces an old sync result', async () => {
    let deleted = false
    mockApi({
      'GET /api/assets': () => ({ data: deleted ? [alexPro] : [alexPro, emmaAir] }),
      'DELETE /api/assets/2': () => {
        deleted = true
        return { message: 'Emma’s MacBook Air was deleted from WorkWize.', returns_on_next_sync: false }
      },
      'DELETE /api/assets/1': { message: 'Alex’s MacBook Pro was deleted from WorkWize.', returns_on_next_sync: true },
      'POST /api/sync': syncSuccess,
    })
    renderAssets()

    await userEvent.click(await screen.findByRole('button', { name: 'Delete Emma’s MacBook Air' }))
    await userEvent.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Delete' }))
    expect(await screen.findByText('Emma’s MacBook Air was deleted from WorkWize.')).toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: 'Sync Now' }))
    expect(await screen.findByText('✓ Sync complete. Laptops: 6 unchanged.')).toBeInTheDocument()
    expect(screen.queryByText('Emma’s MacBook Air was deleted from WorkWize.')).not.toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: 'Delete Alex’s MacBook Pro' }))
    await userEvent.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Delete' }))
    expect(await screen.findByText('Alex’s MacBook Pro was deleted from WorkWize.')).toBeInTheDocument()
    expect(screen.queryByText('✓ Sync complete. Laptops: 6 unchanged.')).not.toBeInTheDocument()
    expect(screen.getAllByRole('status')).toHaveLength(1)
  })

  it('shows "already running" as a calm message, not an error', async () => {
    mockApi({
      'GET /api/assets': { data: [alexPro] },
      'POST /api/sync': [409, { status: 'refused', message: 'A sync is already running. Please wait for it to finish.' }],
    })
    renderAssets()
    await screen.findByText('Assigned to Alex Smith')

    await userEvent.click(screen.getByRole('button', { name: 'Sync Now' }))

    expect(await screen.findByRole('status')).toHaveTextContent('A sync is already running. Please wait for it to finish.')
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
  })

  it('t28: shows "Needs attention (n)", filters the list, and goes back to all laptops', async () => {
    const calls = mockApi({
      'GET /api/assets': assetsReply([alexPro, emmaAir]),
      'GET /api/assets?needs_attention=1': assetsReply([emmaAir]),
    })
    renderAssets()

    const button = await screen.findByRole('button', { name: 'Needs attention (1)' })
    expect(button).toHaveAttribute('aria-pressed', 'false')

    await userEvent.click(button)
    expect(await screen.findByText('Showing only laptops that need attention.')).toBeInTheDocument()
    expect(screen.queryByText('Assigned to Alex Smith')).not.toBeInTheDocument()
    expect(screen.getByText('To be collected from Emma Johnson')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Needs attention (1)' })).toHaveAttribute('aria-pressed', 'true')
    expect(calls).toContain('GET /api/assets?needs_attention=1')

    await userEvent.click(screen.getByRole('button', { name: 'Show all laptops' }))
    expect(await screen.findByText('Assigned to Alex Smith')).toBeInTheDocument()
    expect(screen.queryByText('Showing only laptops that need attention.')).not.toBeInTheDocument()
  })

  it('t28: has no "Needs attention" button when nothing needs a person', async () => {
    mockApi({ 'GET /api/assets': assetsReply([alexPro]) })
    renderAssets()

    await screen.findByText('Assigned to Alex Smith')
    expect(screen.queryByRole('button', { name: /Needs attention/ })).not.toBeInTheDocument()
  })

  it('t28: the button stays after closing the sync message, and disappears once the last one is deleted', async () => {
    let deleted = false
    mockApi({
      'GET /api/assets': () => assetsReply(deleted ? [alexPro] : [alexPro, emmaAir]),
      'GET /api/assets?needs_attention=1': () => assetsReply(deleted ? [] : [emmaAir]),
      'POST /api/sync': syncSuccess,
      'DELETE /api/assets/2': () => {
        deleted = true
        return { message: 'Emma’s MacBook Air was deleted from WorkWize.', returns_on_next_sync: false }
      },
    })
    renderAssets()
    await screen.findByText('Assigned to Alex Smith')

    await userEvent.click(screen.getByRole('button', { name: 'Sync Now' }))
    await screen.findByText('⚠ 1 laptop needs attention')
    await userEvent.click(screen.getByRole('button', { name: 'Close message' }))
    expect(screen.getByRole('button', { name: 'Needs attention (1)' })).toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: 'Needs attention (1)' }))
    await userEvent.click(await screen.findByRole('button', { name: 'Delete Emma’s MacBook Air' }))
    await userEvent.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Delete' }))

    expect(await screen.findByText('Assigned to Alex Smith')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /Needs attention/ })).not.toBeInTheDocument()
    expect(screen.queryByText('Showing only laptops that need attention.')).not.toBeInTheDocument()
  })

  it('shows a laptop name that looks like web code as plain text, never as code', async () => {
    const tricky = { ...alexPro, id: 9, device_name: '<img src=x onerror=alert(1)>' }
    mockApi({ 'GET /api/assets': { data: [tricky] } })
    const { container } = renderAssets()

    expect(await screen.findByRole('link', { name: '<img src=x onerror=alert(1)>' })).toBeInTheDocument()
    expect(container.querySelector('img')).toBeNull()
  })
})
