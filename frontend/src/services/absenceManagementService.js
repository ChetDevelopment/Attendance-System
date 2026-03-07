import api from './api'

const formatError = (error) => {
  if (error.response?.status === 422) {
    return {
      message: error.response.data?.message || 'Validation failed.',
      errors: error.response.data?.errors || {},
    }
  }

  return {
    message: error.response?.data?.message || error.message || 'Absence management request failed.',
    errors: {},
  }
}

export const absenceManagementService = {
  // Get absence statistics
  async getStats(params = {}) {
    try {
      const response = await api.get('/admin/absences/stats', { params })
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  // List all absences with filters
  async getAbsences(params = {}) {
    try {
      const response = await api.get('/admin/absences', { params })
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  // Get absence details
  async getAbsenceDetail(id) {
    try {
      const response = await api.get(`/admin/absences/${id}`)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  // Update absence reason
  async updateReason(id, payload) {
    try {
      const response = await api.put(`/admin/absences/${id}/reason`, payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  // Add comment to absence
  async addComment(id, payload) {
    try {
      const response = await api.post(`/admin/absences/${id}/comment`, payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  // Update absence status (excused/unexcused)
  async updateStatus(id, payload) {
    try {
      const response = await api.patch(`/admin/absences/${id}/status`, payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  // Add follow-up notes
  async addFollowUp(id, payload) {
    try {
      const response = await api.post(`/admin/absences/${id}/follow-up`, payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  // Get student absence history
  async getStudentHistory(studentId, params = {}) {
    try {
      const response = await api.get(`/admin/absences/student/${studentId}/history`, { params })
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  // Bulk update status
  async bulkUpdateStatus(payload) {
    try {
      const response = await api.post('/admin/absences/bulk-status', payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },
}
