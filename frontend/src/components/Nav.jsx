import { NavLink } from 'react-router'

export default function Nav() {
  return (
    <header className="nav">
      <span className="nav-brand">WorkWize</span>
      <nav aria-label="Main">
        <NavLink to="/" end>Home</NavLink>
        <NavLink to="/assets">Assets</NavLink>
        <NavLink to="/employees">Employees</NavLink>
      </nav>
    </header>
  )
}
