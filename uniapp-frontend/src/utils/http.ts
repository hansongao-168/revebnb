import { API_BASE } from '@/config'
import { useUserStore } from '@/store/user'

export interface ApiEnvelope<T> {
  success: boolean
  data: T
  message?: string
}

function parseJson<T>(data: unknown): T {
  if (typeof data === 'string') {
    return JSON.parse(data) as T
  }

  return data as T
}

export function request<T>(options: {
  url: string
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE'
  data?: unknown
  header?: Record<string, string>
}): Promise<T> {
  const userStore = useUserStore()
  const url = options.url.startsWith('http') ? options.url : `${API_BASE}${options.url}`

  return new Promise((resolve, reject) => {
    uni.request({
      url,
      method: options.method ?? 'GET',
      data: options.data,
      header: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(userStore.token ? { Authorization: `Bearer ${userStore.token}` } : {}),
        ...options.header,
      },
      success: (res) => {
        if (res.statusCode === 401) {
          userStore.clearUserInfo()
          uni.reLaunch({ url: '/pages/login/index' })
          reject(res)
          return
        }

        if (res.statusCode >= 400) {
          reject(res)
          return
        }

        resolve(parseJson<T>(res.data))
      },
      fail: (err) => {
        reject(err)
      },
    })
  })
}

export const http = {
  get: <T>(url: string, header?: Record<string, string>) =>
    request<T>({ url, method: 'GET', header }),

  post: <T>(url: string, data?: unknown, header?: Record<string, string>) =>
    request<T>({ url, method: 'POST', data, header }),

  put: <T>(url: string, data?: unknown, header?: Record<string, string>) =>
    request<T>({ url, method: 'PUT', data, header }),
}
