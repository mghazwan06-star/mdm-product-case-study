// Builds the simple (Tier 1) sync result message from the API's sync result.
// Tier 2 replaces the attention line with the full action-first box.

export function syncMessage(result) {
  if (result.status !== 'success') {
    return { tone: 'error', lines: [`✕ ${result.message}`] }
  }

  const counts = result.counts
  const attention = counts.became_to_be_collected
  const lines = []

  if (attention > 0) {
    lines.push(
      attention === 1
        ? "⚠ 1 laptop now needs collecting. It's marked in the list below."
        : `⚠ ${attention} laptops now need collecting. They're marked in the list below.`,
    )
  }

  // A laptop that became "to be collected" is also counted as updated by the
  // backend; on screen it's shown once, in the line above.
  const parts = [
    [counts.assets_created, 'new'],
    [counts.assets_updated - attention, 'updated'],
    [counts.assets_unchanged, 'unchanged'],
  ]
    .filter(([n]) => n > 0)
    .map(([n, word]) => `${n} ${word}`)

  lines.push(parts.length ? `✓ Sync complete: ${parts.join(', ')}.` : '✓ Sync complete.')

  for (const [reason, n] of skippedByReason(result.skipped)) {
    lines.push(`${n} skipped: ${reason}.`)
  }

  return { tone: attention > 0 ? 'warn' : 'ok', lines }
}

function skippedByReason(skipped) {
  const byReason = new Map()
  for (const { reason } of skipped) {
    byReason.set(reason, (byReason.get(reason) ?? 0) + 1)
  }
  return byReason
}
