const raw = import.meta.env.VITE_API_BASE_URL ?? ''

export const API_BASE = typeof raw === 'string' ? raw.replace(/\/$/, '') : ''
