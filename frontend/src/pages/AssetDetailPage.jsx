import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router'
import { api } from '../api'
import ConfirmDialog from '../components/ConfirmDialog'
import StatusLabel from '../components/StatusLabel'
import Unreadable from '../components/Unreadable'
import { ownerLine } from '../lib/labels'
import { ErrorMessage, Loading } from '../components/States'
import { useDelete } from '../components/useDelete'
import { deleteLaptopText } from '../lib/confirmations'
import {
  NOT_PROVIDED,
  date,
  dateTime,
  orNotProvided,
  percent,
  providerName,
  purchaseDateWithAge,
  ramGb,
  yesNo,
} from '../lib/format'

// The wireframe's "Asset Details": essentials first, everything else behind
// "Additional information" (docs/DECISIONS.md, Screens).
export default function AssetDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [asset, setAsset] = useState(null)
  const [error, setError] = useState(null)
  const deletion = useDelete(
    (target) => api.deleteAsset(target.id),
    () => navigate('/assets'),
  )

  useEffect(() => {
    api.asset(id).then(setAsset).catch((e) => setError(e.message))
  }, [id])

  return (
    <main className="page">
      <p className="back"><Link to="/assets">← All laptops</Link></p>
      {error && <ErrorMessage>{error}</ErrorMessage>}
      {!asset && !error && <Loading>Loading laptop…</Loading>}
      {asset && <AssetDetails asset={asset} onDelete={() => deletion.ask(asset)} />}
      {deletion.target && (
        <ConfirmDialog
          title="Delete laptop"
          text={deleteLaptopText(deletion.target)}
          busy={deletion.busy}
          error={deletion.error}
          onConfirm={deletion.confirm}
          onCancel={deletion.cancel}
        />
      )}
    </main>
  )
}

function AssetDetails({ asset, onDelete }) {
  const specs = asset.attributes ?? {}
  const owner = asset.owner
  // Fields Jamf sent in a form WorkWize can't use, with what Jamf sent.
  const unreadable = specs.unreadable ?? {}
  const show = (key, formatted) => (key in unreadable ? <Unreadable value={unreadable[key]} /> : formatted)

  return (
    <>
      <div className="detail-header">
        <h1>{asset.device_name}</h1>
        <StatusLabel asset={asset} />
        <button type="button" className="button button-small detail-delete" onClick={onDelete}>
          Delete
        </button>
      </div>

      <section className="card" aria-label="Essentials">
        <Fields
          rows={[
            ['Owner', ownerLine(asset)],
            ['Model', show('model', orNotProvided(specs.model))],
            ['Processor', show('processor', orNotProvided(specs.processor))],
            ['Memory', show('ram_mb', ramGb(specs.ram_mb))],
            ['Serial number', asset.serial_code],
          ]}
        />
      </section>

      <details className="card more">
        <summary>Additional information</summary>

        <h2>Owner details</h2>
        {owner ? (
          <Fields
            rows={[
              ['Email', orNotProvided(owner.email)],
              ['Job title', orNotProvided(owner.job_title)],
              ['Phone', orNotProvided(owner.phone)],
            ]}
          />
        ) : (
          <Fields
            rows={[
              ['Last owner', orNotProvided(asset.last_owner_name)],
              ['Last owner email', orNotProvided(asset.last_owner_email)],
            ]}
          />
        )}

        <h2>Hardware</h2>
        <Fields
          rows={[
            ['Brand', show('make', orNotProvided(specs.make))],
            ['Cores', show('cores', orNotProvided(specs.cores))],
            ['Battery health', show('battery_percent', percent(specs.battery_percent))],
            ['Platform', show('platform', orNotProvided(specs.platform))],
            ['Model identifier', show('model_identifier', orNotProvided(specs.model_identifier))],
          ]}
        />

        <h2>Lifecycle</h2>
        <Fields
          rows={[
            ['Purchase date', show('purchase_date', purchaseDateWithAge(specs.purchase_date))],
            ['Purchased', show('purchased', yesNo(specs.purchased))],
            ['Last check-in with Jamf', show('last_contact_at', dateTime(asset.last_contact_at))],
            ['Last report', show('last_report_at', date(asset.last_report_at))],
          ]}
        />

        <h2>Source and technical</h2>
        <Fields
          rows={[
            ['Provider', providerName(asset.provider)],
            ['Jamf ID', show('external_id', orNotProvided(asset.external_id))],
            ['Device UDID', show('udid', orNotProvided(asset.udid))],
            ['Jamf site', show('site', orNotProvided(specs.site))],
            ['Last synced into WorkWize', dateTime(asset.last_synced_at)],
          ]}
        />
      </details>
    </>
  )
}

function Fields({ rows }) {
  return (
    <dl className="fields">
      {rows.map(([label, value]) => (
        <div key={label} className="field">
          <dt>{label}</dt>
          <dd className={value === NOT_PROVIDED ? 'missing' : undefined}>{value}</dd>
        </div>
      ))}
    </dl>
  )
}
