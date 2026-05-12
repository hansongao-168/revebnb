import { API_BASE } from '@/config'
import type { ApiEnvelope } from '@/utils/http'
import type { UserProfile } from '@/pages/login/types'
import { useUserStore } from '@/store/user'

export function uploadAvatarApi(filePath: string): Promise<ApiEnvelope<UserProfile>> {
  const userStore = useUserStore()
  const base = API_BASE || ''
  const url = `${base}/api/user/avatar`

  return new Promise((resolve, reject) => {
    uni.uploadFile({
      url,
      filePath,
      name: 'avatar',
      header: {
        Authorization: `Bearer ${userStore.token}`,
        Accept: 'application/json',
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

        const body = typeof res.data === 'string' ? JSON.parse(res.data) : res.data
        resolve(body as ApiEnvelope<UserProfile>)
      },
      fail: (err) => {
        reject(err)
      },
    })
  })
}
