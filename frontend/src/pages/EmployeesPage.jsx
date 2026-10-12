import { useEffect, useState } from 'react'
import { api } from '../api'
import ConfirmDialog from '../components/ConfirmDialog'
import LatestMessage from '../components/LatestMessage'
import { Empty, ErrorMessage, Loading } from '../components/States'
import { useSync } from '../components/syncState'
import { useDelete } from '../components/useDelete'
import { deletePersonText } from '../lib/confirmations'
import { orNotProvided } from '../lib/format'
import { laptopCount } from '../lib/text'

// Tier 1: the people list with laptop counts and delete. Badges, the
// "Needs attention" filter and the person page come in Tier 3.
export default function EmployeesPage() {
  const { version } = useSync()
  const [people, setPeople] = useState(null)
  const [error, setError] = useState(null)
  const [reload, setReload] = useState(0)
  const deletion = useDelete(
    (person) => api.deleteEmployee(person.id),
    () => setReload((n) => n + 1),
  )

  useEffect(() => {
    api.employees()
      .then((data) => {
        setPeople(data)
        setError(null)
      })
      .catch((e) => setError(e.message))
  }, [version, reload])

  return (
    <main className="page">
      <h1>Employees</h1>
      <LatestMessage />

      {error && <ErrorMessage>{error}</ErrorMessage>}
      {!people && !error && <Loading>Loading people…</Loading>}
      {people?.length === 0 && <Empty>No people yet. Press Sync Now to import them from Jamf.</Empty>}

      {people?.length > 0 && (
        <ul className="list">
          {people.map((person) => (
            <li key={person.id} className="list-row">
              <div className="list-main">
                <span className="list-title">{person.name}</span>
                <span className="list-sub">{orNotProvided(person.job_title)} · {person.email}</span>
              </div>
              <span className="count">{laptopCount(person.assets_count)}</span>
              <button
                type="button"
                className="button button-small"
                onClick={() => deletion.ask(person)}
                aria-label={`Delete ${person.name}`}
              >
                Delete
              </button>
            </li>
          ))}
        </ul>
      )}

      {deletion.target && (
        <ConfirmDialog
          title="Delete person"
          text={deletePersonText(deletion.target)}
          busy={deletion.busy}
          error={deletion.error}
          onConfirm={deletion.confirm}
          onCancel={deletion.cancel}
        />
      )}
    </main>
  )
}
