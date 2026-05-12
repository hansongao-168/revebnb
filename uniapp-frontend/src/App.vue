<script lang="ts">
import { useUserStore } from '@/store/user'
import { setupRouteInterceptor } from '@/interceptors/router'

export default {
  onLaunch() {
    const userStore = useUserStore()
    userStore.loadFromStorage()
    setupRouteInterceptor()
  },
}
</script>

<style>
/* Global styles can go here */
</style>
