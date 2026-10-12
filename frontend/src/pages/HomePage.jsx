import { useEffect, useState } from 'react'
import { Link } from 'react-router'
import { api } from '../api'
import { ErrorMessage, Loading } from '../components/States'
import { laptops, people } from '../lib/text'

// Tier 1 home: totals and where to go. The attention list and last sync come later.
export default function HomePage() {
  const [counts, setCounts] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    Promise.all([api.assets(), api.employees()])
      .then(([assets, employees]) => setCounts({ assets: assets.length, employees: employees.length }))
      .catch((e) => setError(e.message))
  }, [])

  return (
    <main className="page">
      <h1>Home</h1>
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
