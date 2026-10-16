import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    setupFiles: './src/test/setup.js',
    // A busy computer can take just over 5 s (the default) for the first test in a file
    // while the test tools load; seen 16 Oct 2026. 15 s only gives slow runs more time.
    testTimeout: 15000,
  },
  server: {
    // The website calls /api/...; Vite passes those requests to the Laravel backend.
    proxy: {
      '/api': process.env.API_PROXY_TARGET ?? 'http://localhost:8000',
    },
  },
})
