import api from './api'

const toError = (error, fallback) => {
  if (error.response?.data?.message) return error.response.data.message
  if (error.message) return error.message
  return fallback
}

export const collaborationService = {
  /**
   * Get shared activity feed across all roles
   */
  async getActivityFeed(params = {}) {
    try {
      const response = await api.get('/collaboration/activity-feed', { params })
      return response.data.activities ?? []
    } catch (error) {
      throw new Error(toError(error, 'Failed to load activity feed.'))
    }
  },

  /**
   * Get team members across all roles
   */
  async getTeamMembers(params = {}) {
    try {
      const response = await api.get('/collaboration/team-members', { params })
      return response.data.members ?? []
    } catch (error) {
      throw new Error(toError(error, 'Failed to load team members.'))
    }
  },

  /**
   * Create a cross-role request
   * @param {Object} data - { type, title, description, target_role, priority }
   */
  async createRequest(data) {
    try {
      const response = await api.post('/collaboration/request', data)
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to create request.'))
    }
  },

  /**
   * Get pending requests for current role
   */
  async getPendingRequests() {
    try {
      const response = await api.get('/collaboration/requests')
      return response.data.requests ?? []
    } catch (error) {
      throw new Error(toError(error, 'Failed to load pending requests.'))
    }
  },

  /**
   * Get collaboration stats
   */
  async getStats() {
    try {
      const response = await api.get('/collaboration/stats')
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to load collaboration stats.'))
    }
  },

  /**
   * Get quick stats for dashboard widgets
   */
  async getQuickStats() {
    try {
      const response = await api.get('/collaboration/quick-stats')
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to load quick stats.'))
    }
  },

  /**
   * Resolve a cross-role request
   * @param {number} id - Request ID
   * @param {string} status - 'approved' or 'rejected'
   */
  async resolveRequest(id, status) {
    try {
      const response = await api.put(`/collaboration/requests/${id}`, { status })
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to resolve request.'))
    }
  },
}