import { syncMessage } from '../lib/syncMessage'
import { useSync } from './syncState'

// Tier 1 result message. Stays until closed. Tier 2 grows this into the
// action-first box (attention items, "Show details") without changing pages.
export default function SyncMessage() {
  const { result, clearResult } = useSync()

  if (!result) return null

  const { tone, lines } = syncMessage(result)

  return (
    <div className={`message message-${tone}`} role={tone === 'error' ? 'alert' : 'status'}>
      <div>
        {lines.map((line) => (
          <p key={line}>{line}</p>
        ))}
      </div>
      <button type="button" className="message-close" onClick={clearResult} aria-label="Close message">
        ×
      </button>
    </div>
  )
}
