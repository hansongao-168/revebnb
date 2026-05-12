/// <reference types="vite/client" />

declare const uni: UniNamespace.Uni

declare namespace UniNamespace {
  interface Uni {
    request(options: UniRequestOptions): UniTask
    navigateTo(options: UniNavigateToOptions): void
    redirectTo(options: UniRedirectToOptions): void
    reLaunch(options: UniReLaunchOptions): void
    showToast(options: UniShowToastOptions): void
    login(options: UniLoginOptions): Promise<UniLoginRes>
    getStorageSync(key: string): unknown
    setStorageSync(key: string, data: unknown): void
    removeStorageSync(key: string): void
    addInterceptor(type: string, options: Record<string, unknown>): void
  }

  interface UniRequestOptions {
    url: string
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'OPTIONS'
    data?: unknown
    header?: Record<string, string>
    success?: (result: UniRequestSuccessCallbackResult) => void
    fail?: (err: UniGeneralCallbackResult) => void
    complete?: () => void
  }

  interface UniRequestSuccessCallbackResult {
    statusCode: number
    data: unknown
  }

  interface UniGeneralCallbackResult {
    errMsg: string
  }

  interface UniTask {}

  interface UniNavigateToOptions {
    url: string
  }

  interface UniRedirectToOptions {
    url: string
  }

  interface UniReLaunchOptions {
    url: string
  }

  interface UniShowToastOptions {
    title: string
    icon?: 'success' | 'loading' | 'none' | 'error'
  }

  interface UniLoginOptions {
    provider: string
    onlyAuthorize?: boolean
  }

  interface UniLoginRes {
    code?: string
    errMsg: string
  }
}

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
