import api from './api'

const formatError = (error) => {
  if (error.response?.status === 422) {
    return {
      message: error.response.data?.message || 'Validation failed.',
      errors: error.response.data?.errors || {},
    }
  }

  return {
    message: error.response?.data?.message || error.message || 'Academic request failed.',
    errors: {},
  }
}

export const adminAcademicService = {
  async getAcademicYears() {
    try {
      const response = await api.get('/admin/academic-years')
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async createAcademicYear(payload) {
    try {
      const response = await api.post('/admin/academic-years', payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async updateAcademicYear(id, payload) {
    try {
      const response = await api.put(`/admin/academic-years/${id}`, payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async deleteAcademicYear(id) {
    try {
      const response = await api.delete(`/admin/academic-years/${id}`)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async getClasses() {
    try {
      const response = await api.get('/admin/classes')
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async createClass(payload) {
    try {
      const response = await api.post('/admin/classes', payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async updateClass(id, payload) {
    try {
      const response = await api.put(`/admin/classes/${id}`, payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async deleteClass(id) {
    try {
      const response = await api.delete(`/admin/classes/${id}`)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },
}

