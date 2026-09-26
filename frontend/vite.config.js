import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

const backendUrl = process.env.BACKEND_URL || 'http://127.0.0.1:8000'
const securityHeaders = {
  'X-Content-Type-Options': 'nosniff',
  'X-Frame-Options': 'SAMEORIGIN',
  'Referrer-Policy': 'strict-origin-when-cross-origin',
  'Permissions-Policy': 'camera=(), geolocation=(), microphone=()',
  'Content-Security-Policy': "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self' ws: wss:; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'",
}
const proxy = {
  '/api': { target: backendUrl, changeOrigin: true },
  '/sanctum': { target: backendUrl, changeOrigin: true },
  '/storage': { target: backendUrl, changeOrigin: true },
}

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  server: {
    host: process.env.HOST || '127.0.0.1',
    port: parseInt(process.env.PORT || '5173'),
    headers: securityHeaders,
    proxy,
  },
  preview: {
    host: process.env.HOST || '127.0.0.1',
    headers: securityHeaders,
    proxy,
  },
})
