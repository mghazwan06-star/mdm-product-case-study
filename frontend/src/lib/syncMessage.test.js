import { describe, expect, it } from 'vitest'
import { syncMessage } from './syncMessage'

function success(counts, skipped = []) {
  return {
    status: 'success',
    counts: {
      assets_created: 0,
      assets_updated: 0,
      assets_unchanged: 0,
      employees_created: 0,
      employees_updated: 0,
      employees_unchanged: 0,
      became_to_be_collected: 0,
      ...counts,
    },
    skipped,
  }
}

describe('syncMessage', () => {
  it('shows one line on a normal day and hides zeros', () => {
    expect(syncMessage(success({ assets_unchanged: 7, employees_unchanged: 6 }))).toEqual({
      tone: 'ok',
      lines: ['✓ Sync complete. Laptops: 7 unchanged.'],
    })
  })

  it('says what was counted, including people', () => {
    expect(syncMessage(success({ assets_created: 7, employees_created: 6 })).lines).toEqual([
      '✓ Sync complete. Laptops: 7 new. People: 6 new.',
    ])
  })

  it('says when it was the very first sync', () => {
    expect(syncMessage({ ...success({ assets_created: 7, employees_created: 6 }), is_first_sync: true }).lines).toEqual([
      '✓ First sync complete: 7 laptops and 6 people added.',
    ])
    expect(syncMessage({ ...success({ assets_created: 1, employees_created: 1 }), is_first_sync: true }).lines).toEqual([
      '✓ First sync complete: 1 laptop and 1 person added.',
    ])
  })

  it('still gives skip reasons on the first sync', () => {
    const message = syncMessage({
      ...success({ assets_created: 6, employees_created: 5 }, [{ serial: null, external_id: '104', reason: 'no serial number' }]),
      is_first_sync: true,
    })

    expect(message.lines).toEqual(['✓ First sync complete: 6 laptops and 5 people added.', '1 skipped: no serial number.'])
  })

  it('mentions people only when something changed for them', () => {
    expect(
      syncMessage(success({ assets_updated: 2, assets_unchanged: 5, employees_created: 1, employees_unchanged: 5 })).lines,
    ).toEqual(['✓ Sync complete. Laptops: 2 updated, 5 unchanged. People: 1 new.'])
    expect(syncMessage(success({ assets_unchanged: 7, employees_updated: 1, employees_unchanged: 5 })).lines).toEqual([
      '✓ Sync complete. Laptops: 7 unchanged. People: 1 updated.',
    ])
  })

  it('shows new, updated and unchanged laptops when they happen', () => {
    expect(syncMessage(success({ assets_created: 2, assets_updated: 1, assets_unchanged: 4 })).lines).toEqual([
      '✓ Sync complete. Laptops: 2 new, 1 updated, 4 unchanged.',
    ])
  })

  it('leads with the laptops that need collecting, singular', () => {
    const message = syncMessage(success({ assets_updated: 1, assets_unchanged: 6, became_to_be_collected: 1 }))

    expect(message.tone).toBe('warn')
    expect(message.lines[0]).toBe("⚠ 1 laptop now needs collecting. It's marked in the list below.")
  })

  it('uses the plural for several laptops', () => {
    const message = syncMessage(success({ assets_updated: 2, assets_unchanged: 5, became_to_be_collected: 2 }))

    expect(message.lines[0]).toBe("⚠ 2 laptops now need collecting. They're marked in the list below.")
  })

  it('does not count a laptop that needs collecting as updated too', () => {
    const message = syncMessage(success({ assets_updated: 2, assets_unchanged: 5, became_to_be_collected: 1 }))

    expect(message.lines[1]).toBe('✓ Sync complete. Laptops: 1 updated, 5 unchanged.')
  })

  it('always gives the reason for skipped laptops', () => {
    const message = syncMessage(
      success({ assets_created: 6 }, [
        { serial: null, external_id: '104', reason: 'no serial number' },
        { serial: 'A', external_id: '1', reason: 'listed twice with conflicting information' },
        { serial: 'A', external_id: '2', reason: 'listed twice with conflicting information' },
      ]),
    )

    expect(message.lines).toEqual([
      '✓ Sync complete. Laptops: 6 new.',
      '1 skipped: no serial number.',
      '2 skipped: listed twice with conflicting information.',
    ])
  })

  it('shows the failure message from the backend', () => {
    const message = syncMessage({
      status: 'failed',
      message: "Couldn't read the Jamf list. Nothing was changed. Try again later.",
    })

    expect(message).toEqual({
      tone: 'error',
      lines: ["✕ Couldn't read the Jamf list. Nothing was changed. Try again later."],
    })
  })

  it('never writes "(s)"', () => {
    for (const n of [1, 2]) {
      const lines = syncMessage(success({ assets_updated: n, became_to_be_collected: n })).lines
      expect(lines.join(' ')).not.toContain('(s)')
    }
  })
})
