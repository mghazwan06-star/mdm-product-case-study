import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router'
import { describe, expect, it } from 'vitest'
import LatestMessage from '../components/LatestMessage'
import { SyncProvider } from '../components/SyncContext'
import { mockApi } from '../test/api'
import AssetDetailPage from './AssetDetailPage'

const macMini = {
  id: 5,
  device_name: 'QA Mac Mini',
  serial_code: 'MACMINIM1QA',
  status: 'assigned',
  owner: { id: 4, name: 'Mia Wilson', email: 'mia.wilson@company.test', job_title: 'QA Engineer', phone: null },
  last_owner_name: 'Mia Wilson',
  last_owner_email: 'mia.wilson@company.test',
  attributes: {
    make: 'Apple',
    model: 'Mac mini (M1, 2020)',
    model_identifier: 'Macmini9,1',
    processor: 'Apple M1',
    cores: 8,
    ram_mb: 16384,
    battery_percent: null,
    platform: 'Mac',
    site: 'None',
    purchased: true,
    purchase_date: '2021-05-20',
  },
  provider: 'jamf',
  external_id: '105',
  udid: '831D5992-39A0-4056-9B26-F9D95C0973B9',
  last_contact_at: '2026-01-02T16:45:00+00:00',
  last_report_at: '2025-10-01T11:25:00+00:00',
  last_seen_in_mdm_at: '2026-10-12T20:00:00+00:00',
  last_synced_at: '2026-10-12T20:00:00+00:00',
}

function AssetsStub() {
  return (
    <>
      <p>Assets page</p>
      <LatestMessage />
    </>
  )
}

function renderDetail(id = 5) {
  return render(
    <MemoryRouter initialEntries={[`/assets/${id}`]}>
      <SyncProvider>
        <Routes>
          <Route path="/assets/:id" element={<AssetDetailPage />} />
          <Route path="/assets" element={<AssetsStub />} />
        </Routes>
      </SyncProvider>
    </MemoryRouter>,
  )
}

describe('AssetDetailPage', () => {
  it('shows the name, status and the essentials', async () => {
    mockApi({ 'GET /api/assets/5': { data: macMini } })
    renderDetail()

    expect(await screen.findByRole('heading', { name: 'QA Mac Mini' })).toBeInTheDocument()
    expect(screen.getByText('Assigned to Mia Wilson')).toBeInTheDocument()

    const essentials = within(screen.getByRole('region', { name: 'Essentials' }))
    expect(essentials.getByText('Mia Wilson')).toBeInTheDocument()
    expect(essentials.getByText('Mac mini (M1, 2020)')).toBeInTheDocument()
    expect(essentials.getByText('Apple M1')).toBeInTheDocument()
    expect(essentials.getByText('16 GB')).toBeInTheDocument()
    expect(essentials.getByText('MACMINIM1QA')).toBeInTheDocument()
  })

  it('keeps additional information closed until asked for', async () => {
    mockApi({ 'GET /api/assets/5': { data: macMini } })
    renderDetail()
    const more = (await screen.findByText('Additional information')).closest('details')

    expect(more).not.toHaveAttribute('open')
    await userEvent.click(screen.getByText('Additional information'))
    expect(more).toHaveAttribute('open')
  })

  it('says "Not provided by Jamf" for the missing battery and phone', async () => {
    mockApi({ 'GET /api/assets/5': { data: macMini } })
    renderDetail()
    await screen.findByRole('heading', { name: 'QA Mac Mini' })

    const battery = screen.getByText('Battery health').closest('.field')
    const phone = screen.getByText('Phone').closest('.field')
    expect(battery).toHaveTextContent('Not provided by Jamf')
    expect(phone).toHaveTextContent('Not provided by Jamf')
    expect(screen.getByText('Provider').closest('.field')).toHaveTextContent('Jamf')
  })

  it('shows the last owner when nobody has the laptop now', async () => {
    mockApi({
      'GET /api/assets/2': {
        data: { ...macMini, id: 2, status: 'to_be_collected', owner: null, last_owner_name: 'Emma Johnson', last_owner_email: 'emma.johnson@company.test' },
      },
    })
    renderDetail(2)

    expect(await screen.findByText('To be collected from Emma Johnson')).toBeInTheDocument()
    expect(screen.getByText('No current owner')).toBeInTheDocument()
    expect(screen.getByText('Last owner email').closest('.field')).toHaveTextContent('emma.johnson@company.test')
  })

  it('shows a plain message for a laptop that no longer exists', async () => {
    mockApi({ 'GET /api/assets/999': [404, { message: "This laptop doesn't exist. It may have been deleted." }] })
    renderDetail(999)

    expect(await screen.findByRole('alert')).toHaveTextContent("This laptop doesn't exist. It may have been deleted.")
    expect(screen.getByRole('link', { name: '← All laptops' })).toHaveAttribute('href', '/assets')
  })

  it('deletes after confirmation and goes back to the list with a message', async () => {
    mockApi({
      'GET /api/assets/5': { data: macMini },
      'DELETE /api/assets/5': { message: 'QA Mac Mini was deleted from WorkWize.', returns_on_next_sync: true },
    })
    renderDetail()

    await userEvent.click(await screen.findByRole('button', { name: 'Delete' }))
    expect(screen.getByRole('dialog')).toHaveTextContent(
      'Delete QA Mac Mini? This only removes it from WorkWize. Jamf still lists it, so it will come back on the next sync.',
    )
    await userEvent.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Delete' }))

    expect(await screen.findByText('Assets page')).toBeInTheDocument()
    expect(screen.getByRole('status')).toHaveTextContent('QA Mac Mini was deleted from WorkWize.')
  })
})
