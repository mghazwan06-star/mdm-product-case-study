import { useState } from 'react'
import { api } from '../api'
import { SyncContext } from './syncState'

// State shared by every page: the Sync Now button, a version number that pages
// watch to reload their lists, and ONE message slot. Only the latest message
// shows (a sync result or a short notice such as "… was deleted"); starting a
// new sync or delete clears the old one. Messages stay until closed.
export function SyncProvider({ children }) {
  const [syncing, setSyncing] = useState(false)
  const [message, setMessage] = useState(null)
  const [version, setVersion] = useState(0)

  async function runSync() {
    if (syncing) return
    setSyncing(true)
    setMessage(null)
    try {
      setMessage({ type: 'sync', result: await api.sync() })
    } catch (e) {
      setMessage({ type: 'sync', result: e.body?.status ? e.body : { status: 'failed', message: e.message } })
    } finally {
      setSyncing(false)
      setVersion((v) => v + 1)
    }
  }

  const value = {
    syncing,
    message,
    version,
    runSync,
    showNotice: (text) => setMessage({ type: 'notice', text }),
    clearMessage: () => setMessage(null),
  }

  return <SyncContext value={value}>{children}</SyncContext>
}
