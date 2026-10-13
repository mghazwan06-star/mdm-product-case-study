import { describe, expect, it } from 'vitest'
import { deleteLaptopText, deletePersonText } from './confirmations'

describe('deleteLaptopText', () => {
  it('warns that a laptop Jamf still lists will come back', () => {
    for (const status of ['assigned', 'owner_removed_locally']) {
      expect(deleteLaptopText({ device_name: 'Alex’s MacBook Pro', status })).toBe(
        'Delete Alex’s MacBook Pro? This only removes it from WorkWize. Jamf still lists it, so it will come back on the next sync.',
      )
    }
  })

  it('says a laptop Jamf no longer assigns will not come back', () => {
    for (const status of ['to_be_collected', 'missing_from_mdm']) {
      expect(deleteLaptopText({ device_name: 'Emma’s MacBook Air', status })).toBe(
        "Delete Emma’s MacBook Air? Jamf no longer assigns this laptop, so it won't come back unless Jamf assigns it to someone again. Do this once it has been collected or retired.",
      )
    }
  })
})

describe('deletePersonText', () => {
  it('tells someone deleting a person with laptops to reassign them in Jamf first', () => {
    expect(deletePersonText({ name: 'Alex Smith', assets_count: 2 })).toBe(
      "Alex Smith had 2 laptops in Jamf at the last sync. Deleting them only removes them from WorkWize, and they'll come back with their laptops on the next sync. To remove them permanently, reassign or remove their laptops in Jamf first. Continue?",
    )
  })

  it('uses the singular for one laptop', () => {
    expect(deletePersonText({ name: 'Emma Johnson', assets_count: 1 })).toContain('Emma Johnson had 1 laptop in Jamf')
  })

  it('keeps it short for a person without laptops', () => {
    expect(deletePersonText({ name: 'Sam', assets_count: 0 })).toBe('Delete Sam? This only removes them from WorkWize.')
  })

  it('never writes "(s)"', () => {
    for (const n of [1, 2]) {
      expect(deletePersonText({ name: 'A', assets_count: n })).not.toContain('(s)')
    }
  })
})
