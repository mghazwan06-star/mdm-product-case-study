import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { useState } from 'react'
import { describe, expect, it } from 'vitest'
import ConfirmDialog from './ConfirmDialog'

function Page({ removeTriggerOnConfirm = false }) {
  const [open, setOpen] = useState(false)
  const [gone, setGone] = useState(false)

  return (
    <main>
      <h1>Asset Management</h1>
      <a href="/elsewhere">A link behind the dialog</a>
      {!gone && (
        <button type="button" onClick={() => setOpen(true)}>
          Delete QA Mac Mini
        </button>
      )}
      {open && (
        <ConfirmDialog
          title="Delete laptop"
          text="Delete QA Mac Mini?"
          onConfirm={() => {
            setOpen(false)
            if (removeTriggerOnConfirm) setGone(true)
          }}
          onCancel={() => setOpen(false)}
        />
      )}
    </main>
  )
}

async function openWithKeyboard() {
  const user = userEvent.setup()
  screen.getByRole('button', { name: 'Delete QA Mac Mini' }).focus()
  await user.keyboard('{Enter}')
  return user
}

describe('ConfirmDialog keyboard use', () => {
  // The first test in a freshly started copy loads the test tools from scratch: once it took
  // 5.2 s against the default 5 s limit (warm runs take about 0.5 s). Final check, 16 Oct 2026.
  it('starts on Cancel', async () => {
    render(<Page />)
    await openWithKeyboard()

    expect(screen.getByRole('button', { name: 'Cancel' })).toHaveFocus()
  }, 15000)

  it('keeps Tab and Shift+Tab inside the dialog', async () => {
    render(<Page />)
    const user = await openWithKeyboard()

    await user.tab()
    expect(screen.getByRole('button', { name: 'Delete' })).toHaveFocus()
    await user.tab()
    expect(screen.getByRole('button', { name: 'Cancel' })).toHaveFocus()
    await user.tab({ shift: true })
    expect(screen.getByRole('button', { name: 'Delete' })).toHaveFocus()
  })

  it('closes with Escape and returns focus to the button that opened it', async () => {
    render(<Page />)
    const user = await openWithKeyboard()

    await user.keyboard('{Escape}')

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Delete QA Mac Mini' })).toHaveFocus()
  })

  it('moves focus to the page title when the opening button is gone', async () => {
    render(<Page removeTriggerOnConfirm />)
    const user = await openWithKeyboard()

    await user.tab()
    await user.keyboard('{Enter}')

    expect(screen.queryByRole('button', { name: 'Delete QA Mac Mini' })).not.toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Asset Management' })).toHaveFocus()
  })
})
