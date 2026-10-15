import InfoButton from './InfoButton'
import { UNREADABLE_INFO, UNREADABLE_LABEL } from '../lib/format'

// A value Jamf sent that WorkWize can't use: shown as sent, never guessed and
// never hidden behind "Not provided by Jamf" (P4, P16; docs/DECISIONS.md row 18).
export default function Unreadable({ value }) {
  return (
    <span className="unreadable">
      {UNREADABLE_LABEL}: “{value}”
      <InfoButton text={UNREADABLE_INFO} label="What does this mean?" />
    </span>
  )
}
