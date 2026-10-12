import { syncMessage } from '../lib/syncMessage'
import { useSync } from './syncState'

// The latest message: a sync result (Tier 1 version; Tier 2 grows it into the
// action-first box) or a short notice. Stays until closed.
export default function LatestMessage() {
  const { message, clearMessage } = useSync()

  if (!message) return null

  const { tone, lines } = message.type === 'sync' ? syncMessage(message.result) : { tone: 'ok', lines: [message.text] }

  return (
    <div className={`message message-${tone}`} role={tone === 'error' ? 'alert' : 'status'}>
      <div>
        {lines.map((line) => (
          <p key={line}>{line}</p>
        ))}
      </div>
      <button type="button" className="message-close" onClick={clearMessage} aria-label="Close message">
        ×
      </button>
    </div>
  )
}
