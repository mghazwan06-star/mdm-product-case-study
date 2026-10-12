import { useState } from 'react'

/**
 * State for "ask, then delete": which record is being confirmed, whether the
 * request is running, and any error to show inside the dialog.
 */
export function useDelete(remove, onDeleted) {
  const [target, setTarget] = useState(null)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState(null)

  async function confirm() {
    setBusy(true)
    setError(null)
    try {
      const result = await remove(target)
      setTarget(null)
      onDeleted(result)
    } catch (e) {
      setError(e.message)
    } finally {
      setBusy(false)
    }
  }

  function ask(record) {
    setError(null)
    setTarget(record)
  }

  function cancel() {
    setTarget(null)
    setError(null)
  }

  return { target, busy, error, ask, confirm, cancel }
}
