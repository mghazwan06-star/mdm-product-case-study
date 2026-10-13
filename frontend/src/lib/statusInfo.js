// What each status that needs a person means, and what to do about it
// (shown behind the ⓘ button). Assigned laptops don't need an explanation.

export function statusInfo(status, lastOwnerName) {
  const name = lastOwnerName ?? 'the last owner'

  switch (status) {
    case 'to_be_collected':
      return `Jamf no longer lists an owner for this laptop. It was last with ${name}. Arrange to collect it. If it has been given to someone new, update the owner in Jamf and sync again. Once collected, you can delete it here.`
    case 'missing_from_mdm':
      return 'This laptop was missing from the latest Jamf list. It may have been retired, lost, stolen or reset, or Jamf had a problem. Check in Jamf. If it was retired or lost, you can delete it here. If it comes back in Jamf, this warning clears by itself.'
    case 'owner_removed_locally':
      return 'The person linked to this laptop was deleted in WorkWize, but Jamf still assigns the laptop to them. The next sync will restore them. To change the owner, update it in Jamf.'
    default:
      return null
  }
}
