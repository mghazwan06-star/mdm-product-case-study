import { useEffect, useState } from 'react'
import { Link } from 'react-router'
import { api } from '../api'
import { ErrorMessage, Loading } from '../components/States'
import SyncButton from '../components/SyncButton'
import { useSync } from '../components/syncState'
import LatestMessage from '../components/LatestMessage'
import { laptops, people } from '../lib/text'

// Home: totals and where to go. An overview with the items that need attention and
// the last sync time is planned (docs/DECISIONS.md, Screens).
export default function HomePage() {
  const { version } = useSync()
  const [counts, setCounts] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    Promise.all([api.assets(), api.employees()])
      .then(([assets, employees]) => setCounts({ assets: assets.length, employees: employees.length }))
      .catch((e) => setError(e.message))
  }, [version])

  return (
    <main className="page">
      <div className="page-header">
        <h1>Home</h1>
        <SyncButton />
      </div>
      <LatestMessage />
      {error && <ErrorMessage>{error}</ErrorMessage>}
      {!counts && !error && <Loading>Loading…</Loading>}
      {counts && counts.assets === 0 && counts.employees === 0 && (
        <p className="lead">No data yet. Press Sync Now to import laptops from Jamf.</p>
      )}
      {counts && (counts.assets > 0 || counts.employees > 0) && (
        <p className="lead">
          <Link to="/assets">{laptops(counts.assets)}</Link> · <Link to="/employees">{people(counts.employees)}</Link>
        </p>
      )}
    </main>
  )
}
