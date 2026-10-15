import { useEffect, useState } from 'react'
import { Link } from 'react-router'
import { api } from '../api'
import ConfirmDialog from '../components/ConfirmDialog'
import LatestMessage from '../components/LatestMessage'
import StatusLabel from '../components/StatusLabel'
import { Empty, ErrorMessage, Loading } from '../components/States'
import SyncButton from '../components/SyncButton'
import { useSync } from '../components/syncState'
import { useDelete } from '../components/useDelete'
import { deleteLaptopText } from '../lib/confirmations'

export default function AssetsPage() {
  const { version, syncing } = useSync()
  const [assets, setAssets] = useState(null)
  const [needsAttentionCount, setNeedsAttentionCount] = useState(0)
  const [attentionOnly, setAttentionOnly] = useState(false)
  const [error, setError] = useState(null)
  const [reload, setReload] = useState(0)
  const deletion = useDelete(
    (asset) => api.deleteAsset(asset.id),
    () => setReload((n) => n + 1),
  )

  useEffect(() => {
    api.assets(attentionOnly)
      .then(({ assets, needsAttentionCount }) => {
        // Nothing left to do: go back to the full list (the button disappears: P14).
        if (attentionOnly && needsAttentionCount === 0) {
          setAttentionOnly(false)
          return
        }
        setAssets(assets)
        setNeedsAttentionCount(needsAttentionCount)
        setError(null)
      })
      .catch((e) => setError(e.message))
  }, [version, reload, attentionOnly])

  return (
    <main className="page">
      <div className="page-header">
        <h1>Asset Management</h1>
        <div className="page-actions">
          {needsAttentionCount > 0 && (
            <button
              type="button"
              className="button button-attention"
              aria-pressed={attentionOnly}
              onClick={() => setAttentionOnly((on) => !on)}
            >
              Needs attention ({needsAttentionCount})
            </button>
          )}
          <SyncButton />
        </div>
      </div>
      {attentionOnly && (
        <p className="filter-note">
          Showing only laptops that need attention.{' '}
          <button type="button" className="link-button" onClick={() => setAttentionOnly(false)}>
            Show all laptops
          </button>
        </p>
      )}
      <LatestMessage />

      {/* An old error isn't shown next to "Syncing…"; the sync reloads the page when it's done (F13). */}
      {error && !syncing && <ErrorMessage>{error}</ErrorMessage>}
      {!assets && !error && <Loading>Loading laptops…</Loading>}
      {assets?.length === 0 && <Empty>No laptops yet. Press Sync Now to import laptops from Jamf.</Empty>}

      {assets?.length > 0 && (
        <ul className="list">
          {assets.map((asset) => (
            <li key={asset.id} className="list-row">
              <div className="list-main">
                <Link to={`/assets/${asset.id}`} className="list-title">{asset.device_name}</Link>
                <span className="list-sub">{asset.serial_code}</span>
              </div>
              <StatusLabel asset={asset} />
              <button
                type="button"
                className="button button-small"
                onClick={() => deletion.ask(asset)}
                aria-label={`Delete ${asset.device_name}`}
              >
                Delete
              </button>
            </li>
          ))}
        </ul>
      )}

      {deletion.target && (
        <ConfirmDialog
          title="Delete laptop"
          text={deleteLaptopText(deletion.target)}
          busy={deletion.busy}
          error={deletion.error}
          onConfirm={deletion.confirm}
          onCancel={deletion.cancel}
        />
      )}
    </main>
  )
}
