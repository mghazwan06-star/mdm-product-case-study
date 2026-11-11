import { describe, expect, it } from 'vitest'
import { statusInfo } from './statusInfo'

describe('statusInfo', () => {
  it('explains each status that needs a person, and what to do', () => {
    expect(statusInfo('to_be_collected', 'Emma Johnson')).toBe(
      'Jamf no longer lists an owner for this laptop. It was last with Emma Johnson. Arrange to collect it. If it has been given to someone new, update the owner in Jamf and sync again. Once collected, you can delete it here.',
    )
    expect(statusInfo('missing_from_mdm', 'Lucas Brown')).toBe(
      'This laptop was missing from the latest Jamf list. It was last with Lucas Brown. It may have been retired, lost, stolen or reset, or Jamf had a problem. Ask Lucas Brown or check in Jamf. Nothing was deleted: it stays here until you delete it, once you know it was retired or lost. If it comes back in Jamf, this warning clears by itself.',
    )
    expect(statusInfo('owner_removed_locally', 'Alex Smith')).toContain('The next sync will restore them.')
  })

  it('has nothing to explain for an assigned laptop', () => {
    expect(statusInfo('assigned', 'Alex Smith')).toBeNull()
  })
})
