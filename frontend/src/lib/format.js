import { plural } from './text'

// Formatting for the laptop details page. Missing values are labelled by
// where the gap is (docs/DECISIONS.md row 6), never left blank.

export const NOT_PROVIDED = 'Not provided by Jamf'

export function orNotProvided(value) {
  return value === null || value === undefined || value === '' ? NOT_PROVIDED : String(value)
}

/**
 * Memory is stored in MB (as Jamf sends it) and shown in GB: 16384 → "16 GB".
 */
export function ramGb(megabytes) {
  if (megabytes === null || megabytes === undefined) return NOT_PROVIDED
  const gb = Math.round((megabytes / 1024) * 10) / 10
  return `${gb} GB`
}

export function percent(value) {
  return value === null || value === undefined ? NOT_PROVIDED : `${value}%`
}

export function yesNo(value) {
  if (value === true) return 'Yes'
  if (value === false) return 'No'
  return NOT_PROVIDED
}

const DATE = { day: 'numeric', month: 'short', year: 'numeric' }

/**
 * A moment (Jamf sends UTC) as a date in the viewer's own time zone: "5 Jan 2026".
 */
export function date(iso) {
  if (!iso) return NOT_PROVIDED
  return new Date(iso).toLocaleDateString('en-GB', DATE)
}

/**
 * A moment in the viewer's own time zone: "5 Jan 2026, 11:30".
 */
export function dateTime(iso) {
  if (!iso) return NOT_PROVIDED
  const time = new Date(iso).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
  return `${date(iso)}, ${time}`
}

/**
 * A calendar date with no time ("2022-06-15"), shown as is: "15 Jun 2022".
 */
export function calendarDate(isoDate) {
  if (!isoDate) return NOT_PROVIDED
  return new Date(`${isoDate}T00:00:00Z`).toLocaleDateString('en-GB', { ...DATE, timeZone: 'UTC' })
}

/**
 * Purchase date with the laptop's age: "1 Feb 2023, 3 years 8 months".
 */
export function purchaseDateWithAge(isoDate, today = new Date()) {
  if (!isoDate) return NOT_PROVIDED

  const bought = new Date(`${isoDate}T00:00:00Z`)
  let months = (today.getUTCFullYear() - bought.getUTCFullYear()) * 12 + (today.getUTCMonth() - bought.getUTCMonth())
  if (today.getUTCDate() < bought.getUTCDate()) months -= 1

  const years = Math.floor(months / 12)
  const rest = months % 12
  const age = [years > 0 && plural(years, 'year'), rest > 0 && plural(rest, 'month')].filter(Boolean).join(' ')

  return `${calendarDate(isoDate)}, ${age || 'less than a month'}`
}

const PROVIDER_NAMES = { jamf: 'Jamf' }

export function providerName(provider) {
  return PROVIDER_NAMES[provider] ?? orNotProvided(provider)
}
