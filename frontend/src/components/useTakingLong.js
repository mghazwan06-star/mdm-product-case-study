import { useEffect, useState } from 'react'

export const TAKING_LONG = 'This is taking longer than usual. Still waiting for WorkWize to answer.'

// After 15 seconds of waiting, a status should say so instead of "Loading…"
// forever (P5; finding F13). Normal pages and syncs answer in a few seconds.
export const TAKING_LONG_AFTER_MS = 15000

export function useTakingLong(waiting) {
  const [takingLong, setTakingLong] = useState(false)

  useEffect(() => {
    if (!waiting) return undefined
    const timer = setTimeout(() => setTakingLong(true), TAKING_LONG_AFTER_MS)
    return () => {
      clearTimeout(timer)
      setTakingLong(false)
    }
  }, [waiting])

  return waiting && takingLong
}
