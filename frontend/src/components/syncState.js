import { createContext, useContext } from 'react'

export const SyncContext = createContext(null)

/**
 * The shared sync state: { syncing, result, version, runSync, clearResult }.
 */
export function useSync() {
  return useContext(SyncContext)
}
