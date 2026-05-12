import { http, type ApiEnvelope } from '@/utils/http'
import type { LoginParams, LoginResult, RegisterParams, WechatLoginParams } from '@/pages/login/types'

export const loginApi = (data: LoginParams) => {
  return http.post<ApiEnvelope<LoginResult>>('/api/auth/login', data)
}

export const registerApi = (data: RegisterParams) => {
  return http.post<ApiEnvelope<LoginResult>>('/api/auth/register', data)
}

export const loginByWechatApi = (data: WechatLoginParams) => {
  return http.post<ApiEnvelope<LoginResult>>('/api/auth/wechat-login', data)
}

export const fetchProfileApi = () => {
  return http.get<ApiEnvelope<LoginResult['user']>>('/api/user/profile')
}

export const updateProfileApi = (data: Partial<LoginResult['user']>) => {
  return http.put<ApiEnvelope<LoginResult['user']>>('/api/user/profile', data)
}
