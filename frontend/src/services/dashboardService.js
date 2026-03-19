import api from './api'

const toError = (error) => {
  if (error.response?.data?.message) return error.response.data.message
  if (error.message) return error.message
  return 'Failed to load dashboard data.'
}

export const dashboardService = {
  async getOverview() {
    try {
      const response = await api.get('/admin/dashboard/overview')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getSummary() {
    try {
      const response = await api.get('/admin/dashboard/summary')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getLateStudents() {
    try {
      const response = await api.get('/admin/dashboard/late-students')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getNotifications() {
    try {
      const response = await api.get('/admin/dashboard/notifications')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getActiveSession() {
    try {
      const response = await api.get('/admin/dashboard/active-session')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getRiskStudents() {
    try {
      const response = await api.get('/students/risk')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getTrendData() {
    try {
      const response = await api.get('/reports/trends')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },
}
  