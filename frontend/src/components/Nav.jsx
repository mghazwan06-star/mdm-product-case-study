import { Link, NavLink } from 'react-router'

export default function Nav() {
  return (
    <header className="nav">
      <Link to="/" className="nav-brand">
        <img src="/workwize-logo.png" alt="WorkWize" width="174" height="28" />
      </Link>
      <nav aria-label="Main">
        <NavLink to="/" end>Home</NavLink>
        <NavLink to="/assets">Assets</NavLink>
        <NavLink to="/employees">Employees</NavLink>
      </nav>
    </header>
  )
}
