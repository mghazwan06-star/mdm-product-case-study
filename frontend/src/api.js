// Talks to the Laravel backend. The website never reads the Jamf file itself.

export const UNREACHABLE = "Couldn't reach WorkWize. Check that the app is running, then try again."

// WorkWize answered, but with an error and no explanation (finding F11).
export const SOMETHING_WENT_WRONG = 'Something went wrong in WorkWize. Try again in a minute.'
export const SOMETHING_WENT_WRONG_SYNC = 'Something went wrong during the sync. Nothing was changed. Try again in a minute.'

export class ApiError extends Error {
  constructor(message, status, body = null) {
    super(message)
    this.status = status
    this.body = body
  }
}

// The development server answers 502–504 itself when the backend isn't there.
const BACKEND_NOT_THERE = [502, 503, 504]

async function request(path, options = {}) {
  let response
  try {
    response = await fetch(`/api${path}`, {
      ...options,
      headers: { Accept: 'application/json', ...options.headers },
    })
  } catch {
    throw new ApiError(UNREACHABLE, 0)
  }

  let body = null
  try {
    body = await response.json()
  } catch {
    // No JSON body (e.g. the backend is down behind the proxy, or it crashed).
  }

  if (!response.ok) {
    // Only WorkWize's own plain messages are shown; a technical error page never is (P11).
    const technical = body && ('exception' in body || 'trace' in body)
    if (body?.message && !technical) throw new ApiError(body.message, response.status, body)
    // "Couldn't reach" only when nothing answered; otherwise it answered with an error (F11).
    const nothingAnswered = BACKEND_NOT_THERE.includes(response.status) && !body
    throw new ApiError(nothingAnswered ? UNREACHABLE : SOMETHING_WENT_WRONG, response.status)
  }

  return body
}

export const api = {
  // { assets, needsAttentionCount }; with needsAttentionOnly, only laptops that need a person.
  assets: (needsAttentionOnly = false) =>
    request(needsAttentionOnly ? '/assets?needs_attention=1' : '/assets').then((body) => ({
      assets: body.data,
      needsAttentionCount: body.meta?.needs_attention_count ?? 0,
    })),
  asset: (id) => request(`/assets/${id}`).then((body) => body.data),
  deleteAsset: (id) => request(`/assets/${id}`, { method: 'DELETE' }),
  employees: () => request('/employees').then((body) => body.data),
  deleteEmployee: (id) => request(`/employees/${id}`, { method: 'DELETE' }),
  sync: () => request('/sync', { method: 'POST' }),
}
