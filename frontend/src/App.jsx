import { useEffect, useState } from 'react'
import './App.css'

// Temporary system check page (step 4.1). Replaced by the real pages in step 4.6.
const CHECKS = [
  { key: 'backend', label: 'Backend' },
  { key: 'database', label: 'Database' },
  { key: 'jamf_file', label: 'Jamf file' },
]

function App() {
  const [health, setHealth] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    fetch('/api/health')
      .then((response) => response.json())
      .then(setHealth)
      .catch(() => setError('Could not reach the backend.'))
  }, [])

  return (
    <main className="check">
      <h1>WorkWize MDM Sync</h1>
      <p>System check</p>

      {error && <p className="status error">{error}</p>}
      {!health && !error && <p>Checking…</p>}

      {health && (
        <ul>
          {CHECKS.map(({ key, label }) => (
            <li key={key}>
              <span>{label}</span>
              <span className={`status ${health[key].ok ? 'ok' : 'error'}`}>
                {health[key].ok ? 'OK' : 'Problem'}: {health[key].message}
              </span>
            </li>
          ))}
        </ul>
      )}
    </main>
  )
}

export default App
