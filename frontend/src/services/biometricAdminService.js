import api from './api'

const normalizeError = (error) => ({
  message: error.response?.data?.message || error.message || 'Biometric request failed.',
  errors: error.response?.data?.errors || {},
})

export const biometricAdminService = {
  async getOverview() {
    try {
      const response = await api.get('/admin/biometric/overview')
      return response.data
    } catch (error) {
      throw normalizeError(error)
    }
  },

  async getStudents(params = {}) {
    try {
      const response = await api.get('/admin/biometric/students', { params })
      return response.data
    } catch (error) {
      throw normalizeError(error)
    }
  },

  async getHistory(studentId) {
    try {
      const response = await api.get(`/admin/biometric/students/${studentId}/history`)
      return response.data
    } catch (error) {
      throw normalizeError(error)
    }
  },

  async updateStudent(studentId, payload) {
    try {
      const response = await api.patch(`/admin/biometric/students/${studentId}`, payload)
      return response.data
    } catch (error) {
      throw normalizeError(error)
    }
  },
}
