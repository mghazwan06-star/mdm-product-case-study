import { Link, NavLink } from 'react-router'

export default function Nav() {
  return (
    <header className="nav">
      <Link to="/" className="nav-brand">
        <svg className="nav-brand-icon" viewBox="0 0 32 32" width="28" height="28" aria-hidden="true">
          <rect x="5" y="7" width="22" height="14" rx="2" fill="none" strokeWidth="2.5" />
          <path d="M2 25h28" strokeWidth="2.5" strokeLinecap="round" />
        </svg>
        <span className="nav-brand-name">MDM Device Sync</span>
      </Link>
      <nav aria-label="Main">
        <NavLink to="/" end>Home</NavLink>
        <NavLink to="/assets">Assets</NavLink>
        <NavLink to="/employees">Employees</NavLink>
      </nav>
    </header>
  )
}
