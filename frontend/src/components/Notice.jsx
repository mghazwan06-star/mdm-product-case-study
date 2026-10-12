// A short confirmation that something happened, e.g. "Alex Smith was deleted from WorkWize."
export default function Notice({ children, onClose }) {
  return (
    <div className="message message-ok" role="status">
      <p>{children}</p>
      <button type="button" className="message-close" onClick={onClose} aria-label="Close message">
        ×
      </button>
    </div>
  )
}
