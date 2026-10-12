import { describe, expect, it } from 'vitest'
import { NOT_PROVIDED, date, dateTime, orNotProvided, percent, purchaseDateWithAge, ramGb, yesNo } from './format'

describe('missing values', () => {
  it('says "Not provided by Jamf" instead of leaving a gap', () => {
    for (const empty of [null, undefined, '']) {
      expect(orNotProvided(empty)).toBe(NOT_PROVIDED)
    }
    expect(percent(null)).toBe('Not provided by Jamf')
    expect(ramGb(null)).toBe('Not provided by Jamf')
    expect(yesNo(null)).toBe('Not provided by Jamf')
    expect(dateTime(null)).toBe('Not provided by Jamf')
  })

  it('keeps real values, including zero', () => {
    expect(orNotProvided('Apple')).toBe('Apple')
    expect(orNotProvided(0)).toBe('0')
    expect(percent(91)).toBe('91%')
    expect(yesNo(false)).toBe('No')
  })
})

describe('ramGb', () => {
  it('shows memory in GB', () => {
    expect(ramGb(8192)).toBe('8 GB')
    expect(ramGb(16384)).toBe('16 GB')
    expect(ramGb(32768)).toBe('32 GB')
    expect(ramGb(12000)).toBe('11.7 GB')
  })
})

describe('dates', () => {
  // The tests run in a fixed time zone (see "test" in package.json).
  it('runs in the Amsterdam time zone', () => {
    expect(Intl.DateTimeFormat().resolvedOptions().timeZone).toBe('Europe/Amsterdam')
  })

  it('shows Jamf times (UTC) in the viewer\'s local time', () => {
    expect(dateTime('2026-01-05T10:30:12+00:00')).toBe('5 Jan 2026, 11:30') // winter: UTC+1
    expect(dateTime('2026-10-12T19:10:00+00:00')).toBe('12 Oct 2026, 21:10') // summer: UTC+2
  })

  it('moves the date when local time is past midnight', () => {
    expect(date('2025-11-20T23:30:00+00:00')).toBe('21 Nov 2025')
  })

  it('keeps calendar dates as they are, in any time zone', () => {
    expect(purchaseDateWithAge('2022-06-15', new Date('2026-10-12T12:00:00Z'))).toBe('15 Jun 2022, 4 years 3 months')
  })

  it('adds the laptop age to the purchase date', () => {
    const today = new Date('2026-10-12T12:00:00Z')

    expect(purchaseDateWithAge('2023-02-01', today)).toBe('1 Feb 2023, 3 years 8 months')
    expect(purchaseDateWithAge('2025-10-12', today)).toBe('12 Oct 2025, 1 year')
    expect(purchaseDateWithAge('2026-09-01', today)).toBe('1 Sept 2026, 1 month')
    expect(purchaseDateWithAge('2026-10-01', today)).toBe('1 Oct 2026, less than a month')
    expect(purchaseDateWithAge(null, today)).toBe('Not provided by Jamf')
  })
})
