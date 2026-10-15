import { laptops as laptopsText, people as peopleText } from './text'

// Builds the sync result message from the API's sync result:
// - header + attention: laptops that NEWLY need a person in this sync, by name, first (P5)
//   (the message is the news; the labels in the list are the record),
// - lines: the summary and skip reasons, with zero counts left out,
// - details: every count, zeros included, behind "Show details" (P13).

export function syncMessage(result) {
  // Another sync is running: not an error, just wait.
  if (result.status === 'refused') {
    return { tone: 'info', header: null, attention: [], lines: [result.message], details: null }
  }

  if (result.status !== 'success') {
    return { tone: 'error', header: null, attention: [], lines: [`✕ ${result.message}`], details: null }
  }

  const counts = result.counts
  const attention = (result.needs_attention ?? []).map(attentionItem)
  const header = attention.length
    ? `⚠ ${laptopsText(attention.length)} ${attention.length === 1 ? 'needs' : 'need'} attention`
    : null

  // A laptop that became "to be collected" is also counted as updated by the
  // backend; on screen it's shown once, in the attention list.
  const newlyToBeCollected = counts.became_to_be_collected ?? 0
  const updated = counts.assets_updated - newlyToBeCollected

  const lines = []

  if (result.is_first_sync) {
    lines.push(
      `✓ First sync complete: ${laptopsText(counts.assets_created)} and ${peopleText(counts.employees_created)} added.`,
    )
  } else {
    const laptops = countList([
      [counts.assets_created, 'new'],
      [updated, 'updated'],
      [counts.assets_unchanged, 'unchanged'],
    ])
    // People are only mentioned when something changed for them.
    const people = countList([
      [counts.employees_created, 'new'],
      [counts.employees_updated, 'updated'],
    ])

    let summary = '✓ Sync complete.'
    if (result.list_was_empty) {
      summary += " Jamf's list was empty, so every laptop is now marked 'No longer in Jamf'. Nothing was deleted."
    }
    if (laptops) summary += ` Laptops: ${laptops}.`
    if (people) summary += ` People: ${people}.`
    lines.push(summary)
  }

  for (const [reason, n] of skippedByReason(result.skipped)) {
    lines.push(`${n} skipped: ${reason}.`)
  }

  const details = [
    `Laptops: ${counts.assets_created} new, ${updated} updated, ${counts.assets_unchanged} unchanged, ${newlyToBeCollected} now to be collected, ${counts.became_missing ?? 0} now no longer in Jamf.`,
    `People: ${counts.employees_created} new, ${counts.employees_updated} updated, ${counts.employees_unchanged} unchanged.`,
    `Skipped: ${counts.skipped ?? result.skipped.length}.`,
  ]

  return { tone: attention.length ? 'warn' : 'ok', header, attention, lines, details }
}

/**
 * One attention item, e.g. "To be collected from Emma Johnson: Emma’s MacBook Air".
 * The laptop name becomes a link straight to its page (P18), so it's kept separate from the text around it.
 */
function attentionItem(item) {
  const person = item.person_name ?? 'the last owner'
  const parts =
    item.status === 'missing_from_mdm'
      ? { before: 'No longer in Jamf', after: ` (last seen with ${person})` }
      : { before: `To be collected from ${person}`, after: '' }

  return { assetId: item.asset_id, status: item.status, deviceName: item.device_name, personName: item.person_name, ...parts }
}

/**
 * "2 updated, 5 unchanged" with zero counts left out; empty when all are zero.
 */
function countList(pairs) {
  return pairs
    .filter(([n]) => n > 0)
    .map(([n, word]) => `${n} ${word}`)
    .join(', ')
}

function skippedByReason(skipped) {
  const byReason = new Map()
  for (const { reason } of skipped) {
    byReason.set(reason, (byReason.get(reason) ?? 0) + 1)
  }
  return byReason
}
