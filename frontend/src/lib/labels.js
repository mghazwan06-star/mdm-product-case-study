// Status labels for laptops (copy register, Part I.2).

export function statusLabel(asset) {
  const last = asset.last_owner_name

  switch (asset.status) {
    case 'assigned':
      return `Assigned to ${asset.owner?.name ?? last}`
    case 'to_be_collected':
      return last ? `To be collected from ${last}` : 'To be collected'
    case 'missing_from_mdm':
      return last ? `No longer in Jamf · last seen with ${last}` : 'No longer in Jamf'
    case 'owner_removed_locally':
      return last
        ? `Owner removed in WorkWize (${last}) · returns on next sync`
        : 'Owner removed in WorkWize · returns on next sync'
    default:
      return asset.status
  }
}

/**
 * Colour of the label: amber for statuses that need a person.
 */
export function statusTone(status) {
  if (status === 'assigned') return 'ok'
  if (status === 'to_be_collected' || status === 'missing_from_mdm') return 'warn'
  return 'neutral'
}
