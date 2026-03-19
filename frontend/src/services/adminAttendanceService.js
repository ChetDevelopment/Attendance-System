import api from './api'

const formatError = (error) => {
  if (error.response?.status === 422) {
    return {
      message: error.response.data?.message || 'Validation failed.',
      errors: error.response.data?.errors || {},
    }
  }

  return {
    message: error.response?.data?.message || error.message || 'Attendance request failed.',
    errors: {},
  }
}

export const adminAttendanceService = {
  async getRecords(params = {}) {
    try {
      const response = await api.get('/admin/attendance-records', { params })
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async updateRecord(id, payload) {
    try {
      const response = await api.patch(`/admin/attendance-records/${id}`, payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async unlockRecord(id) {
    try {
      const response = await api.post(`/admin/attendance-records/${id}/unlock`)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async manualCorrection(payload) {
    try {
      const response = await api.post('/admin/attendance-records/manual-correction', payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async getSessions() {
    try {
      const response = await api.get('/admin/sessions')
      return Array.isArray(response.data?.data) ? response.data.data : []
    } catch (error) {
      throw formatError(error)
    }
  },
}

