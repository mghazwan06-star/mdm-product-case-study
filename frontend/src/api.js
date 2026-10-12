// Talks to the Laravel backend. The website never reads the Jamf file itself.

export const UNREACHABLE = "Couldn't reach WorkWize. Check that the app is running, then try again."

export class ApiError extends Error {
  constructor(message, status, body = null) {
    super(message)
    this.status = status
    this.body = body
  }
}

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
    // No JSON body (e.g. the backend is down behind the proxy).
  }

  if (!response.ok) {
    throw new ApiError(body?.message ?? UNREACHABLE, response.status, body)
  }

  return body
}

export const api = {
  assets: () => request('/assets').then((body) => body.data),
  asset: (id) => request(`/assets/${id}`).then((body) => body.data),
  deleteAsset: (id) => request(`/assets/${id}`, { method: 'DELETE' }),
  employees: () => request('/employees').then((body) => body.data),
  deleteEmployee: (id) => request(`/employees/${id}`, { method: 'DELETE' }),
  sync: () => request('/sync', { method: 'POST' }),
}
