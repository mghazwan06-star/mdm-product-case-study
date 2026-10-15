import { act, render, screen } from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { Loading } from './States'

const TAKING_LONG = 'This is taking longer than usual. Still waiting for WorkWize to answer.'

describe('Loading (F13)', () => {
  beforeEach(() => vi.useFakeTimers())
  afterEach(() => vi.useRealTimers())

  it('adds a "taking longer than usual" line after 15 seconds, not before', () => {
    render(<Loading>Loading people…</Loading>)
    expect(screen.getByRole('status')).toHaveTextContent('Loading people…')

    act(() => vi.advanceTimersByTime(14900))
    expect(screen.queryByText(TAKING_LONG)).not.toBeInTheDocument()

    act(() => vi.advanceTimersByTime(100))
    expect(screen.getByRole('status')).toHaveTextContent(TAKING_LONG)
  })
})
