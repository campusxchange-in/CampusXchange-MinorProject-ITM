import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
//
// All URLs come from vite-project/.env  (VITE_API_BASE_URL).
// Going live = change that one value (and FRONTEND_URL / PHP_BASE_URL in
// config/app_config.php). No component or service file has a URL inside it.
//
// Optional: set VITE_USE_DEV_PROXY=true in .env to let the Vite dev server
// forward /backend and /uploads to VITE_API_BASE_URL (avoids CORS in dev).
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const apiBase = env.VITE_API_BASE_URL || 'http://localhost/CX'
  const useProxy = env.VITE_USE_DEV_PROXY === 'true'

  return {
    plugins: [react()],
    server: {
      port: 5173,       // must match FRONTEND_URL in config/app_config.php
      strictPort: true,
      ...(useProxy && {
        proxy: {
          '/backend': { target: apiBase, changeOrigin: true },
          '/uploads': { target: apiBase, changeOrigin: true },
        },
      }),
    },
  }
})
