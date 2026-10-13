import { statusLabel, statusTone } from '../lib/labels'
import { statusInfo } from '../lib/statusInfo'
import InfoButton from './InfoButton'

export default function StatusLabel({ asset }) {
  return (
    <span className="status-wrap">
      <span className={`status status-${statusTone(asset.status)}`}>{statusLabel(asset)}</span>
      <InfoButton text={statusInfo(asset.status, asset.last_owner_name)} label="What does this status mean?" />
    </span>
  )
}
