import { useSync } from './syncState'

export default function SyncButton() {
  const { syncing, runSync } = useSync()

  return (
    <button type="button" className="button button-primary" onClick={runSync} disabled={syncing}>
      {syncing ? 'Syncing…' : 'Sync Now'}
    </button>
  )
}
