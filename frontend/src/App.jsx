import { Route, Routes } from 'react-router'
import Nav from './components/Nav'
import AssetsPage from './pages/AssetsPage'
import EmployeesPage from './pages/EmployeesPage'
import HomePage from './pages/HomePage'
import NotFoundPage from './pages/NotFoundPage'
import './App.css'

export default function App() {
  return (
    <>
      <Nav />
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/assets" element={<AssetsPage />} />
        <Route path="/employees" element={<EmployeesPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </>
  )
}
