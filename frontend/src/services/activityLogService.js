import api from './api'

const normalizeError = (error) => ({
  message: error.response?.data?.message || error.message || 'Activity log request failed.',
  errors: error.response?.data?.errors || {},
})

export const activityLogService = {
  async list(params = {}) {
    try {
      const response = await api.get('/admin/activity-logs', { params })
      return response.data
    } catch (error) {
      throw normalizeError(error)
    }
  },
}
