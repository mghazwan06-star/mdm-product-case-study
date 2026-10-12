import { useState } from 'react'
import { useSync } from './syncState'

/**
 * State for "ask, then delete": which record is being confirmed, whether the
 * request is running, and any error to show inside the dialog. Starting a
 * delete clears the old message; success shows the backend's "… was deleted".
 */
export function useDelete(remove, onDeleted = () => {}) {
  const { clearMessage, showNotice } = useSync()
  const [target, setTarget] = useState(null)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState(null)

  async function confirm() {
    setBusy(true)
    setError(null)
    clearMessage()
    try {
      const result = await remove(target)
      setTarget(null)
      showNotice(result.message)
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
