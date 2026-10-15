import { useId, useState } from 'react'

// The ⓘ button: shows a short explanation below it, and hides it again.
// Context on demand, without crowding the everyday view (P15).
export default function InfoButton({ text, label = 'What does this mean?' }) {
  const [open, setOpen] = useState(false)
  const id = useId()

  if (!text) return null

  return (
    <>
      <button
        type="button"
        className="info-button"
        aria-label={label}
        aria-expanded={open}
        aria-controls={id}
        onClick={() => setOpen((o) => !o)}
      >
        ⓘ
      </button>
      {open && (
        <span id={id} className="info-text">
          {text}
        </span>
      )}
    </>
  )
}
