// Loading, empty and error states with plain messages.

export function Loading({ children }) {
  return <p className="state" role="status">{children}</p>
}

export function Empty({ children }) {
  return <p className="state">{children}</p>
}

export function ErrorMessage({ children }) {
  return <p className="state state-error" role="alert">{children}</p>
}
