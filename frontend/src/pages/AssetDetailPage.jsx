import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router'
import { api } from '../api'
import StatusLabel from '../components/StatusLabel'
import { ErrorMessage, Loading } from '../components/States'
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
// "Additional information" (decision 6.2). Info symbols come in Tier 3.
export default function AssetDetailPage() {
  const { id } = useParams()
  const [asset, setAsset] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    api.asset(id).then(setAsset).catch((e) => setError(e.message))
  }, [id])

  return (
    <main className="page">
      <p className="back"><Link to="/assets">← All laptops</Link></p>
      {error && <ErrorMessage>{error}</ErrorMessage>}
      {!asset && !error && <Loading>Loading laptop…</Loading>}
      {asset && <AssetDetails asset={asset} />}
    </main>
  )
}

function AssetDetails({ asset }) {
  const specs = asset.attributes ?? {}
  const owner = asset.owner

  return (
    <>
      <div className="detail-header">
        <h1>{asset.device_name}</h1>
        <StatusLabel asset={asset} />
      </div>

      <section className="card" aria-label="Essentials">
        <Fields
          rows={[
            ['Owner', owner ? owner.name : 'No current owner'],
            ['Model', orNotProvided(specs.model)],
            ['Processor', orNotProvided(specs.processor)],
            ['Memory', ramGb(specs.ram_mb)],
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
            ['Brand', orNotProvided(specs.make)],
            ['Cores', orNotProvided(specs.cores)],
            ['Battery health', percent(specs.battery_percent)],
            ['Platform', orNotProvided(specs.platform)],
            ['Model identifier', orNotProvided(specs.model_identifier)],
          ]}
        />

        <h2>Lifecycle</h2>
        <Fields
          rows={[
            ['Purchase date', purchaseDateWithAge(specs.purchase_date)],
            ['Purchased', yesNo(specs.purchased)],
            ['Last check-in with Jamf', dateTime(asset.last_contact_at)],
            ['Last report', date(asset.last_report_at)],
          ]}
        />

        <h2>Source and technical</h2>
        <Fields
          rows={[
            ['Provider', providerName(asset.provider)],
            ['Jamf ID', orNotProvided(asset.external_id)],
            ['Device UDID', orNotProvided(asset.udid)],
            ['Jamf site', orNotProvided(specs.site)],
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
