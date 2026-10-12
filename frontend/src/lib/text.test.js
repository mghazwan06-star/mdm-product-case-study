import { describe, expect, it } from 'vitest'
import { laptopCount, laptops, people, plural } from './text'

describe('plural', () => {
  it('uses the singular for exactly one', () => {
    expect(plural(1, 'laptop')).toBe('1 laptop')
    expect(people(1)).toBe('1 person')
    expect(plural(1, 'skipped', 'skipped')).toBe('1 skipped')
  })

  it('uses the plural for zero and for more than one', () => {
    expect(laptops(0)).toBe('0 laptops')
    expect(laptops(2)).toBe('2 laptops')
    expect(people(6)).toBe('6 people')
  })

  it('never writes "(s)"', () => {
    for (const n of [0, 1, 2, 7]) {
      expect(laptops(n)).not.toContain('(s)')
      expect(people(n)).not.toContain('(s)')
    }
  })
})

describe('laptopCount', () => {
  it('says "No laptops" instead of 0', () => {
    expect(laptopCount(0)).toBe('No laptops')
    expect(laptopCount(1)).toBe('1 laptop')
    expect(laptopCount(2)).toBe('2 laptops')
  })
})
