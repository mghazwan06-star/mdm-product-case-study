import { statusLabel, statusTone } from '../lib/labels'

export default function StatusLabel({ asset }) {
  return <span className={`status status-${statusTone(asset.status)}`}>{statusLabel(asset)}</span>
}
