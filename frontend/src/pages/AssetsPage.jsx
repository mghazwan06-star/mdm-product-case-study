import { useEffect, useState } from 'react'
import { Link } from 'react-router'
import { api } from '../api'
import StatusLabel from '../components/StatusLabel'
import { Empty, ErrorMessage, Loading } from '../components/States'
import SyncButton from '../components/SyncButton'
import { useSync } from '../components/syncState'
import SyncMessage from '../components/SyncMessage'

export default function AssetsPage() {
  const { version } = useSync()
  const [assets, setAssets] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    api.assets()
      .then((data) => {
        setAssets(data)
        setError(null)
      })
      .catch((e) => setError(e.message))
  }, [version])

  return (
    <main className="page">
      <div className="page-header">
        <h1>Asset Management</h1>
        <SyncButton />
      </div>
      <SyncMessage />

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
            </li>
          ))}
        </ul>
      )}
    </main>
  )
}
