import { vi } from 'vitest'

/**
 * Fakes the backend: each key is "METHOD /api/path", each value a body,
 * a [status, body] pair, an Error (network failure) or a function returning one of those.
 */
export function mockApi(routes) {
  const calls = []
  vi.spyOn(globalThis, 'fetch').mockImplementation(async (url, options = {}) => {
    const key = `${options.method ?? 'GET'} ${url}`
    calls.push(key)
    let reply = routes[key]
    if (typeof reply === 'function') reply = reply()
    if (reply === undefined) throw new Error(`No fake reply for ${key}`)
    if (reply instanceof Error) throw reply
    const [status, body] = Array.isArray(reply) ? reply : [200, reply]
    return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })
  })
  return calls
}

export const alexPro = {
  id: 1,
  device_name: 'Alex’s MacBook Pro',
  serial_code: 'C02DL0XYZQ6N',
  status: 'assigned',
  owner: { id: 1, name: 'Alex Smith' },
  last_owner_name: 'Alex Smith',
}

export const emmaAir = {
  id: 2,
  device_name: 'Emma’s MacBook Air',
  serial_code: 'FVFH90ABC123',
  status: 'to_be_collected',
  owner: null,
  last_owner_name: 'Emma Johnson',
}

/**
 * A laptops-list reply with the "needs attention" count, as the backend sends it.
 */
export function assetsReply(assets) {
  const count = assets.filter((a) => ['to_be_collected', 'missing_from_mdm', 'owner_removed_locally'].includes(a.status)).length
  return { data: assets, meta: { needs_attention_count: count } }
}

