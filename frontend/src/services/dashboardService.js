import api from './api'
import { getUserRole } from './auth'

const toError = (error) => {
  if (error.response?.data?.message) return error.response.data.message
  if (error.message) return error.message
  return 'Failed to load dashboard data.'
}

const ensureDashboardAccess = (...allowedRoles) => {
  const role = getUserRole()
  return allowedRoles.includes(role)
}

export const dashboardService = {
  async getOverview() {
    if (!ensureDashboardAccess('admin', 'teacher', 'education')) {
      return {
        summary: {},
        late_students: [],
        offsite_students: [],
        active_session: null,
        trends: [],
        risk_students: [],
      }
    }

    try {
      const response = await api.get('/admin/dashboard/overview')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getSummary() {
    if (!ensureDashboardAccess('admin', 'teacher', 'education')) {
      return {}
    }

    try {
      const response = await api.get('/admin/dashboard/summary')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getLateStudents() {
    if (!ensureDashboardAccess('admin', 'teacher', 'education')) {
      return []
    }

    try {
      const response = await api.get('/admin/dashboard/late-students')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getNotifications() {
    if (!ensureDashboardAccess('admin', 'teacher', 'education')) {
      return []
    }

    try {
      const response = await api.get('/admin/dashboard/notifications')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getActiveSession() {
    if (!ensureDashboardAccess('admin', 'teacher', 'education')) {
      return null
    }

    try {
      const response = await api.get('/admin/dashboard/active-session')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getRiskStudents() {
    if (!ensureDashboardAccess('admin', 'teacher', 'education')) {
      return []
    }

    try {
      const response = await api.get('/students/risk')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  async getTrendData() {
    if (!ensureDashboardAccess('admin', 'teacher', 'education')) {
      return []
    }

    try {
      const response = await api.get('/reports/trends')
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },

  /**
   * Export class summary report for education dashboard
   */
  async exportClassSummary() {
    if (!ensureDashboardAccess('admin', 'teacher', 'education')) {
      throw new Error('Unauthorized')
    }

    try {
      const response = await api.get('/education/reports/export/class-summary', {
        responseType: 'blob',
      })
      return response.data
    } catch (error) {
      throw new Error(toError(error))
    }
  },
}
  
