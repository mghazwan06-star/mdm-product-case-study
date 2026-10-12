import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router'
import './index.css'
import App from './App.jsx'
import { SyncProvider } from './components/SyncContext'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <BrowserRouter>
      <SyncProvider>
        <App />
      </SyncProvider>
    </BrowserRouter>
  </StrictMode>,
)
