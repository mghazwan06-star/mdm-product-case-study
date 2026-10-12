import { useEffect, useRef } from 'react'

// A small confirmation dialog inside the page. Escape or Cancel closes it.
export default function ConfirmDialog({ title, text, confirmLabel = 'Delete', busy = false, error, onConfirm, onCancel }) {
  const cancelRef = useRef(null)

  useEffect(() => {
    cancelRef.current?.focus()
    const onKey = (e) => e.key === 'Escape' && onCancel()
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [onCancel])

  return (
    <div className="dialog-backdrop">
      <div className="dialog" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
        <h2 id="dialog-title">{title}</h2>
        <p>{text}</p>
        {error && <p className="state state-error" role="alert">{error}</p>}
        <div className="dialog-actions">
          <button type="button" className="button" onClick={onCancel} ref={cancelRef} disabled={busy}>
            Cancel
          </button>
          <button type="button" className="button button-danger" onClick={onConfirm} disabled={busy}>
            {busy ? 'Deleting…' : confirmLabel}
          </button>
        </div>
      </div>
    </div>
  )
}
