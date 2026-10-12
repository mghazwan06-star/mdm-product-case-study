import { useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router'
import { api } from '../api'
import ConfirmDialog from '../components/ConfirmDialog'
import Notice from '../components/Notice'
import StatusLabel from '../components/StatusLabel'
import { Empty, ErrorMessage, Loading } from '../components/States'
import SyncButton from '../components/SyncButton'
import { useSync } from '../components/syncState'
import SyncMessage from '../components/SyncMessage'
import { useDelete } from '../components/useDelete'
import { deleteLaptopText } from '../lib/confirmations'

export default function AssetsPage() {
  const { version } = useSync()
  const location = useLocation()
  const [assets, setAssets] = useState(null)
  const [error, setError] = useState(null)
  const [notice, setNotice] = useState(location.state?.notice ?? null)
  const [reload, setReload] = useState(0)
  const deletion = useDelete(
    (asset) => api.deleteAsset(asset.id),
    (result) => {
      setNotice(result.message)
      setReload((n) => n + 1)
    },
  )

  useEffect(() => {
    api.assets()
      .then((data) => {
        setAssets(data)
        setError(null)
      })
      .catch((e) => setError(e.message))
  }, [version, reload])

  return (
    <main className="page">
      <div className="page-header">
        <h1>Asset Management</h1>
        <SyncButton />
      </div>
      <SyncMessage />
      {notice && <Notice onClose={() => setNotice(null)}>{notice}</Notice>}

      {error && <ErrorMessage>{error}</ErrorMessage>}
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
