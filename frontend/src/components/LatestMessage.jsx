import { syncMessage } from '../lib/syncMessage'
import { useSync } from './syncState'

// The latest message: a sync result or a short notice. Stays until closed.
// A fuller version that lists each laptop needing attention is planned (docs/DECISIONS.md, Screens).
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
