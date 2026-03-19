import api from './api'

const formatError = (error) => {
  if (error.response?.status === 422) {
    return {
      message: error.response.data?.message || 'Validation failed.',
      errors: error.response.data?.errors || {},
    }
  }

  return {
    message: error.response?.data?.message || error.message || 'Student request failed.',
    errors: {},
  }
}

export const studentService = {
  async getStudents(page = 1, perPage = 50, filters = {}) {
    try {
      const response = await api.get('/admin/students', {
        params: { page, per_page: perPage, ...filters },
      })
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async createStudent(payload) {
    try {
      const response = await api.post('/admin/students', payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async bulkCreateStudents(payload) {
    try {
      const response = await api.post('/admin/students/bulk', payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async updateStudent(id, payload) {
    try {
      const response = await api.put(`/admin/students/${id}`, payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async deleteStudent(id) {
    try {
      const response = await api.delete(`/admin/students/${id}`)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async uploadStudentPhoto(id, file) {
    try {
      const formData = new FormData()
      formData.append('photo', file)

      const response = await api.post(`/admin/students/${id}/photo`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },
}
