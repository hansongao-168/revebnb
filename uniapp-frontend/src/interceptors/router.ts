import { useUserStore } from '@/store/user'

const whiteList = ['/pages/login/index', '/pages/index/index']

function normalizePath(url: string): string {
  const path = url.split('?')[0]
  if (path.startsWith('/')) {
    return path
  }

  return `/${path}`
}

export function setupRouteInterceptor(): void {
  const guard = (e: { url: string }): boolean => {
    const userStore = useUserStore()
    const path = normalizePath(e.url)
    if (!whiteList.includes(path) && !userStore.isLogined) {
      uni.navigateTo({ url: '/pages/login/index' })
      return false
    }

    return true
  }

  uni.addInterceptor('navigateTo', { invoke: guard })
  uni.addInterceptor('redirectTo', { invoke: guard })
  uni.addInterceptor('reLaunch', { invoke: guard })
}
