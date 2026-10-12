import { Link } from 'react-router'

export default function NotFoundPage() {
  return (
    <main className="page">
      <h1>Page not found</h1>
      <p>This page doesn't exist. <Link to="/">Go to Home</Link></p>
    </main>
  )
}
