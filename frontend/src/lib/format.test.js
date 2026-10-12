import { describe, expect, it } from 'vitest'
import { NOT_PROVIDED, dateTime, orNotProvided, percent, purchaseDateWithAge, ramGb, yesNo } from './format'

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
  it('shows readable dates and times', () => {
    expect(dateTime('2026-01-05T10:30:12+00:00')).toBe('5 Jan 2026, 10:30')
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
