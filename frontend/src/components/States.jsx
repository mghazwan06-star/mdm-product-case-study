import { TAKING_LONG, useTakingLong } from './useTakingLong'

// Loading, empty and error states with plain messages.

export function Loading({ children }) {
  const takingLong = useTakingLong(true)

  return (
    <div className="state" role="status">
      <p>{children}</p>
      {takingLong && <p>{TAKING_LONG}</p>}
    </div>
  )
}

export function Empty({ children }) {
  return <p className="state">{children}</p>
}

export function ErrorMessage({ children }) {
  return <p className="state state-error" role="alert">{children}</p>
}
