import { defineStore } from 'pinia'
import type { UserProfile } from '@/pages/login/types'

const TOKEN_KEY = 'auth_token'
const USER_KEY = 'user_profile'
const APPID_KEY = 'wechat_appid'

export const useUserStore = defineStore('user', {
  state: () => ({
    token: '' as string,
    user: null as UserProfile | null,
    appid: '' as string,
  }),

  getters: {
    isLogined: (state) => Boolean(state.token),
    userInfo: (state) => ({
      ...state.user,
      token: state.token,
      appid: state.appid,
    }),
  },

  actions: {
    loadFromStorage() {
      const t = uni.getStorageSync(TOKEN_KEY)
      const u = uni.getStorageSync(USER_KEY)
      const a = uni.getStorageSync(APPID_KEY)
      if (typeof t === 'string' && t) {
        this.token = t
      }
      if (typeof u === 'string' && u) {
        try {
          this.user = JSON.parse(u) as UserProfile
        } catch {
          this.user = null
        }
      }
      if (typeof a === 'string' && a) {
        this.appid = a
      }
    },

    setWechatAppId(appid: string) {
      this.appid = appid
      uni.setStorageSync(APPID_KEY, appid)
    },

    setUserInfo(payload: { token: string; user: UserProfile }) {
      this.token = payload.token
      this.user = payload.user
      uni.setStorageSync(TOKEN_KEY, payload.token)
      uni.setStorageSync(USER_KEY, JSON.stringify(payload.user))
    },

    patchUser(partial: Partial<UserProfile>) {
      if (!this.user) {
        this.user = { ...partial } as UserProfile
      } else {
        this.user = { ...this.user, ...partial }
      }
      uni.setStorageSync(USER_KEY, JSON.stringify(this.user))
    },

    clearUserInfo() {
      this.token = ''
      this.user = null
      uni.removeStorageSync(TOKEN_KEY)
      uni.removeStorageSync(USER_KEY)
    },
  },
})
