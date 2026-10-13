import { describe, expect, it } from 'vitest'
import { ownerLine, statusLabel, statusTone } from './labels'

const alex = { owner: { name: 'Alex Smith' }, last_owner_name: 'Alex Smith' }
const emma = { owner: null, last_owner_name: 'Emma Johnson' }

describe('statusLabel', () => {
  it('uses the agreed wording for each status', () => {
    expect(statusLabel({ ...alex, status: 'assigned' })).toBe('Assigned to Alex Smith')
    expect(statusLabel({ ...emma, status: 'to_be_collected' })).toBe('To be collected from Emma Johnson')
    expect(statusLabel({ ...emma, status: 'missing_from_mdm' })).toBe('No longer in Jamf · last seen with Emma Johnson')
    expect(statusLabel({ ...emma, status: 'owner_removed_locally' })).toBe(
      'Owner removed in WorkWize (Emma Johnson) · returns on next sync',
    )
  })

  it('never shows an empty name', () => {
    expect(statusLabel({ owner: null, last_owner_name: null, status: 'to_be_collected' })).toBe('To be collected')
  })
})

describe('statusTone', () => {
  it('marks the statuses that need a person', () => {
    expect(statusTone('assigned')).toBe('ok')
    expect(statusTone('to_be_collected')).toBe('warn')
    expect(statusTone('missing_from_mdm')).toBe('warn')
    expect(statusTone('owner_removed_locally')).toBe('neutral')
  })
})

describe('ownerLine', () => {
  it('shows the current owner', () => {
    expect(ownerLine({ ...alex, status: 'assigned' })).toBe('Alex Smith')
  })

  it('shows the last owner when nobody has the laptop now', () => {
    expect(ownerLine({ ...emma, status: 'to_be_collected' })).toBe('No current owner · last owner: Emma Johnson')
  })

  it('says only "No current owner" when no last owner is known', () => {
    expect(ownerLine({ owner: null, last_owner_name: null, status: 'to_be_collected' })).toBe('No current owner')
  })
})

