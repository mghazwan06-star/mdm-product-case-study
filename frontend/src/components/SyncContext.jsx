import { useState } from 'react'
import { api } from '../api'
import { SyncContext } from './syncState'

// Sync state shared by every page: the button, the result message (which
// stays until closed, also when moving between pages) and a version number
// that pages watch to reload their lists after a sync.
export function SyncProvider({ children }) {
  const [syncing, setSyncing] = useState(false)
  const [result, setResult] = useState(null)
  const [version, setVersion] = useState(0)

  async function runSync() {
    if (syncing) return
    setSyncing(true)
    try {
      setResult(await api.sync())
    } catch (e) {
      setResult(e.body?.status ? e.body : { status: 'failed', message: e.message })
    } finally {
      setSyncing(false)
      setVersion((v) => v + 1)
    }
  }

  return (
    <SyncContext value={{ syncing, result, version, runSync, clearResult: () => setResult(null) }}>
      {children}
    </SyncContext>
  )
}
