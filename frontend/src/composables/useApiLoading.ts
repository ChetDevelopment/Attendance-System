import { ref, readonly } from 'vue'

const loadingRequests = ref(0)
const isGlobalLoading = ref(false)

export function useApiLoading() {
  const startRequest = () => {
    loadingRequests.value++
    isGlobalLoading.value = loadingRequests.value > 0
  }

  const endRequest = () => {
    loadingRequests.value = Math.max(0, loadingRequests.value - 1)
    isGlobalLoading.value = loadingRequests.value > 0
  }

  return {
    isGlobalLoading: readonly(isGlobalLoading),
    startRequest,
    endRequest
  }
}

export default useApiLoading

