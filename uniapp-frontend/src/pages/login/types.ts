export interface LoginParams {
  email: string
  password: string
}

export interface RegisterParams {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface UserProfile {
  id: number
  name: string
  email: string
  phone: string | null
  avatar: string | null
  gender: number
  status: number
  email_verified_at?: string | null
}

export interface LoginResult {
  token: string
  user: UserProfile
}

export interface WechatLoginParams {
  appid?: string
  loginCode: string
  telephoneCode?: string
}
