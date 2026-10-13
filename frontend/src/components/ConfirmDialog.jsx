import { useEffect, useRef } from 'react'

// A small confirmation dialog inside the page. Keyboard: focus starts on Cancel,
// Tab and Shift+Tab stay inside the dialog, Escape closes it, and focus goes back
// to where it was (or to the page title if that button is gone, e.g. a deleted row).
export default function ConfirmDialog({ title, text, confirmLabel = 'Delete', busy = false, error, onConfirm, onCancel }) {
  const dialogRef = useRef(null)
  const cancelRef = useRef(null)

  useEffect(() => {
    const returnTo = document.activeElement
    cancelRef.current?.focus()

    return () => {
      if (returnTo?.isConnected) {
        returnTo.focus()
      } else {
        const heading = document.querySelector('main h1')
        heading?.setAttribute('tabindex', '-1')
        heading?.focus()
      }
    }
  }, [])

  useEffect(() => {
    function onKeyDown(e) {
      if (e.key === 'Escape') {
        onCancel()
        return
      }
      if (e.key !== 'Tab') return

      const buttons = [...dialogRef.current.querySelectorAll('button:not([disabled])')]
      if (buttons.length === 0) return
      const first = buttons[0]
      const last = buttons[buttons.length - 1]

      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault()
        last.focus()
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault()
        first.focus()
      } else if (!dialogRef.current.contains(document.activeElement)) {
        e.preventDefault()
        first.focus()
      }
    }

    document.addEventListener('keydown', onKeyDown)
    return () => document.removeEventListener('keydown', onKeyDown)
  }, [onCancel])

  return (
    <div className="dialog-backdrop">
      <div className="dialog" role="dialog" aria-modal="true" aria-labelledby="dialog-title" ref={dialogRef}>
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
