import { laptops } from './text'

// Delete confirmations (copy register, Part I.5).

export function deleteLaptopText(asset) {
  const willReturn = asset.status === 'assigned' || asset.status === 'owner_removed_locally'

  return willReturn
    ? `Delete ${asset.device_name}? This only removes it from WorkWize. Jamf still lists it, so it will come back on the next sync.`
    : `Delete ${asset.device_name}? Jamf no longer assigns this laptop, so it won't come back unless Jamf assigns it to someone again. Do this once it has been collected or retired.`
}

/**
 * Decision D3: a person who still has laptops gets the "root and stem" instructions.
 */
export function deletePersonText(person) {
  if (person.assets_count === 0) {
    return `Delete ${person.name}? This only removes them from WorkWize.`
  }

  return `${person.name} had ${laptops(person.assets_count)} in Jamf at the last sync. Deleting them only removes them from WorkWize, and they'll come back with their laptops on the next sync. To remove them permanently, reassign or remove their laptops in Jamf first. Continue?`
}
