import { useState } from 'react'
import { Link } from 'react-router'
import { statusInfo } from '../lib/statusInfo'
import { syncMessage } from '../lib/syncMessage'
import InfoButton from './InfoButton'
import { useSync } from './syncState'
import { TAKING_LONG, useTakingLong } from './useTakingLong'

// The latest message: a sync result or a short notice. Stays until closed.
export default function LatestMessage() {
  const { message, clearMessage, syncing } = useSync()
  const [showDetails, setShowDetails] = useState(false)
  const takingLong = useTakingLong(syncing)

  if (takingLong) {
    return (
      <div className="message message-info" role="status">
        <div className="message-body">
          <p>{TAKING_LONG}</p>
        </div>
      </div>
    )
  }

  if (!message) return null

  const { tone, header, attention, lines, details } =
    message.type === 'sync'
      ? syncMessage(message.result)
      : { tone: 'ok', header: null, attention: [], lines: [message.text], details: null }

  return (
    <div className={`message message-${tone}`} role={tone === 'error' ? 'alert' : 'status'}>
      <div className="message-body">
        {header && <p className="message-header">{header}</p>}
        {attention.length > 0 && (
          <ul className="attention-list">
            {attention.map((item) => (
              <li key={item.assetId}>
                {item.before}: <Link to={`/assets/${item.assetId}`}>{item.deviceName}</Link>
                {item.after}
                <InfoButton text={statusInfo(item.status, item.personName)} label="What does this status mean?" />
              </li>
            ))}
          </ul>
        )}
        {lines.map((line) => (
          <p key={line}>{line}</p>
        ))}
        {details && (
          <>
            <button
              type="button"
              className="link-button"
              aria-expanded={showDetails}
              onClick={() => setShowDetails((s) => !s)}
            >
              {showDetails ? 'Hide details' : 'Show details'}
            </button>
            {showDetails && (
              <ul className="details-list">
                {details.map((line) => (
                  <li key={line}>{line}</li>
                ))}
              </ul>
            )}
          </>
        )}
      </div>
      <button type="button" className="message-close" onClick={clearMessage} aria-label="Close message">
        ×
      </button>
    </div>
  )
}
