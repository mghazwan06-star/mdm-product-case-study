import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router'
import { describe, expect, it } from 'vitest'
import { SyncProvider } from '../components/SyncContext'
import { mockApi } from '../test/api'
import EmployeesPage from './EmployeesPage'

const alex = { id: 1, name: 'Alex Smith', email: 'alex.smith@company.test', job_title: 'Backend Engineer', assets_count: 2 }
const emma = { id: 2, name: 'Emma Johnson', email: 'emma.johnson@company.test', job_title: null, assets_count: 1 }
const sam = { id: 3, name: 'Sam', email: 'sam@company.test', job_title: 'Intern', assets_count: 0 }

function renderEmployees() {
  return render(<MemoryRouter><SyncProvider><EmployeesPage /></SyncProvider></MemoryRouter>)
}

describe('EmployeesPage', () => {
  it('lists people with their laptop counts, never blank or "0"', async () => {
    mockApi({ 'GET /api/employees': { data: [alex, emma, sam] } })
    renderEmployees()

    const alexRow = (await screen.findByText('Alex Smith')).closest('li')
    expect(alexRow).toHaveTextContent('2 laptops')
    expect(alexRow).toHaveTextContent('Backend Engineer')
    expect(screen.getByText('Emma Johnson').closest('li')).toHaveTextContent('1 laptop')
    expect(screen.getByText('Emma Johnson').closest('li')).toHaveTextContent('Not provided by Jamf')
    expect(screen.getByText('Sam').closest('li')).toHaveTextContent('No laptops')
  })

  it('shows the empty state before the first sync', async () => {
    mockApi({ 'GET /api/employees': { data: [] } })
    renderEmployees()

    expect(await screen.findByText('No people yet. Press Sync Now to import them from Jamf.')).toBeInTheDocument()
  })

  it('gives the D3 instructions before deleting a person with laptops, then deletes', async () => {
    let deleted = false
    const calls = mockApi({
      'GET /api/employees': () => ({ data: deleted ? [emma] : [alex, emma] }),
      'DELETE /api/employees/1': () => {
        deleted = true
        return { message: 'Alex Smith was deleted from WorkWize.', laptops_affected: 2 }
      },
    })
    renderEmployees()

    await userEvent.click(await screen.findByRole('button', { name: 'Delete Alex Smith' }))
    const dialog = screen.getByRole('dialog')
    expect(dialog).toHaveTextContent(
      "Alex Smith had 2 laptops in Jamf at the last sync. Deleting them only removes them from WorkWize, and they'll come back with their laptops on the next sync. To remove them permanently, reassign or remove their laptops in Jamf first. Continue?",
    )

    await userEvent.click(within(dialog).getByRole('button', { name: 'Delete' }))

    expect(await screen.findByText('Alex Smith was deleted from WorkWize.')).toBeInTheDocument()
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    expect(screen.queryByText('Alex Smith')).not.toBeInTheDocument()
    expect(calls).toContain('DELETE /api/employees/1')
  })

  it('deletes nothing when the person cancels', async () => {
    const calls = mockApi({ 'GET /api/employees': { data: [sam] } })
    renderEmployees()

    await userEvent.click(await screen.findByRole('button', { name: 'Delete Sam' }))
    expect(screen.getByRole('dialog')).toHaveTextContent('Delete Sam? This only removes them from WorkWize.')
    await userEvent.click(screen.getByRole('button', { name: 'Cancel' }))

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    expect(calls.filter((c) => c.startsWith('DELETE'))).toEqual([])
  })

  it('shows the plain message if the person was already deleted', async () => {
    mockApi({
      'GET /api/employees': { data: [sam] },
      'DELETE /api/employees/3': [404, { message: "This person doesn't exist. They may have been deleted." }],
    })
    renderEmployees()

    await userEvent.click(await screen.findByRole('button', { name: 'Delete Sam' }))
    await userEvent.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Delete' }))

    expect(await within(screen.getByRole('dialog')).findByRole('alert')).toHaveTextContent(
      "This person doesn't exist. They may have been deleted.",
    )
  })
})
