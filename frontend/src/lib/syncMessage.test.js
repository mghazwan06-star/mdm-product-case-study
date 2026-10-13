import { describe, expect, it } from 'vitest'
import { syncMessage } from './syncMessage'

function success(counts, skipped = [], extra = {}) {
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
      skipped: skipped.length,
      ...counts,
    },
    needs_attention: [],
    skipped,
    ...extra,
  }
}

const emmaItem = { asset_id: 2, status: 'to_be_collected', device_name: 'Emma’s MacBook Air', person_name: 'Emma Johnson' }
const lucasItem = { asset_id: 4, status: 'missing_from_mdm', device_name: 'Design Team MacBook', person_name: 'Lucas Brown' }

describe('syncMessage', () => {
  it('shows one line on a normal day and hides zeros', () => {
    const message = syncMessage(success({ assets_unchanged: 7, employees_unchanged: 6 }))

    expect(message.tone).toBe('ok')
    expect(message.header).toBeNull()
    expect(message.attention).toEqual([])
    expect(message.lines).toEqual(['✓ Sync complete. Laptops: 7 unchanged.'])
  })

  it('says what was counted, including people', () => {
    expect(syncMessage(success({ assets_created: 7, employees_created: 6 })).lines).toEqual([
      '✓ Sync complete. Laptops: 7 new. People: 6 new.',
    ])
  })

  it('says when it was the very first sync', () => {
    expect(syncMessage(success({ assets_created: 7, employees_created: 6 }, [], { is_first_sync: true })).lines).toEqual([
      '✓ First sync complete: 7 laptops and 6 people added.',
    ])
    expect(syncMessage(success({ assets_created: 1, employees_created: 1 }, [], { is_first_sync: true })).lines).toEqual([
      '✓ First sync complete: 1 laptop and 1 person added.',
    ])
  })

  it('still gives skip reasons on the first sync', () => {
    const message = syncMessage(
      success({ assets_created: 6, employees_created: 5 }, [{ serial: null, external_id: '104', reason: 'no serial number' }], {
        is_first_sync: true,
      }),
    )

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

  it('leads with the laptops that newly need attention, by name (singular)', () => {
    const message = syncMessage(
      success({ assets_updated: 1, assets_unchanged: 6, became_to_be_collected: 1 }, [], { needs_attention: [emmaItem] }),
    )

    expect(message.tone).toBe('warn')
    expect(message.header).toBe('⚠ 1 laptop needs attention')
    expect(message.attention).toEqual([
      {
        assetId: 2,
        status: 'to_be_collected',
        deviceName: 'Emma’s MacBook Air',
        personName: 'Emma Johnson',
        before: 'To be collected from Emma Johnson',
        after: '',
      },
    ])
  })

  it('uses the plural and the wording for each status', () => {
    const message = syncMessage(
      success({ assets_updated: 1, assets_unchanged: 5, became_to_be_collected: 1 }, [], {
        needs_attention: [emmaItem, lucasItem],
      }),
    )

    expect(message.header).toBe('⚠ 2 laptops need attention')
    expect(message.attention[1]).toMatchObject({
      before: 'No longer in Jamf',
      deviceName: 'Design Team MacBook',
      after: ' (last seen with Lucas Brown)',
    })
  })

  it('does not count a laptop that needs collecting as updated too', () => {
    const message = syncMessage(
      success({ assets_updated: 2, assets_unchanged: 5, became_to_be_collected: 1 }, [], { needs_attention: [emmaItem] }),
    )

    expect(message.lines[0]).toBe('✓ Sync complete. Laptops: 1 updated, 5 unchanged.')
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

  it('keeps every count, zeros included, for "Show details"', () => {
    const message = syncMessage(
      success({ assets_updated: 1, assets_unchanged: 6, employees_unchanged: 6, became_to_be_collected: 1 }, [], {
        needs_attention: [emmaItem],
      }),
    )

    expect(message.details).toEqual([
      'Laptops: 0 new, 0 updated, 6 unchanged, 1 now to be collected.',
      'People: 0 new, 0 updated, 6 unchanged.',
      'Skipped: 0.',
    ])
  })

  it('shows the failure message from the backend', () => {
    const message = syncMessage({
      status: 'failed',
      message: "Couldn't read the Jamf list. Nothing was changed. Try again later.",
    })

    expect(message.tone).toBe('error')
    expect(message.lines).toEqual(["✕ Couldn't read the Jamf list. Nothing was changed. Try again later."])
    expect(message.details).toBeNull()
  })

  it('asks you to wait when another sync is already running', () => {
    const message = syncMessage({ status: 'refused', message: 'A sync is already running. Please wait for it to finish.' })

    expect(message.tone).toBe('info')
    expect(message.lines).toEqual(['A sync is already running. Please wait for it to finish.'])
  })

  it('never writes "(s)"', () => {
    for (const items of [[emmaItem], [emmaItem, lucasItem]]) {
      const message = syncMessage(success({ assets_updated: 1, became_to_be_collected: 1 }, [], { needs_attention: items }))
      expect([message.header, ...message.lines].join(' ')).not.toContain('(s)')
    }
  })
})
