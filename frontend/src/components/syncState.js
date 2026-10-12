import { createContext, useContext } from 'react'

export const SyncContext = createContext(null)

/**
 * The shared state: { syncing, message, version, runSync, showNotice, clearMessage }.
 */
export function useSync() {
  return useContext(SyncContext)
}
