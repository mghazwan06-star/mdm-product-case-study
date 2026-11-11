import { laptops } from './text'

// Delete confirmations. The wording depends on whether the record comes back on
// the next sync (docs/DECISIONS.md rows 14–16).

export function deleteLaptopText(asset) {
  const willReturn = asset.status === 'assigned' || asset.status === 'owner_removed_locally'

  return willReturn
    ? `Delete ${asset.device_name}? This only removes it from WorkWize. Jamf still lists it, so it will come back on the next sync.`
    : `Delete ${asset.device_name}? Jamf no longer assigns this laptop, so it won't come back unless Jamf assigns it to someone again. Do this once it has been collected or retired.`
}

/**
 * A person who still has laptops gets instructions: reassign or remove their laptops
 * in Jamf first (docs/DECISIONS.md row 15; instructions, not just a warning: P11).
 */
export function deletePersonText(person) {
  if (person.assets_count === 0) {
    return `Delete ${person.name}? This only removes them from WorkWize.`
  }

  // Says first what happens to the laptops: AI testers cancelled when it only said what goes wrong (docs/RESULTS.md, part 2).
  const one = person.assets_count === 1
  return `Delete ${person.name}? Their ${laptops(person.assets_count)} ${one ? 'stays' : 'stay'} in the list, marked "Owner removed". Jamf still assigns ${one ? 'it' : 'them'} to ${person.name}, so ${person.name} will come back on the next sync. To remove ${person.name} for good, reassign or remove the ${one ? 'laptop' : 'laptops'} in Jamf first.`
}
