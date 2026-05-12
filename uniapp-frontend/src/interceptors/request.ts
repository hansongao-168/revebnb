/**
 * Request surface for the app. Global JSON requests and auth headers are implemented in `../utils/http.ts`.
 * Add cross-cutting hooks here if you split `http` later.
 */
export { http, request } from '@/utils/http'
