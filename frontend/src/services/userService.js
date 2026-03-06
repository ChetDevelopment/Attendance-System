import api from './api'

const formatError = (error) => {
  if (error.response?.status === 422) {
    return {
      message: error.response.data?.message || 'Validation failed.',
      errors: error.response.data?.errors || {},
    }
  }

  return {
    message: error.response?.data?.message || error.message || 'User request failed.',
    errors: {},
  }
}

export const userService = {
  async getUsers() {
    try {
      const response = await api.get('/admin/users')
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async getRoles() {
    try {
      const response = await api.get('/admin/roles')
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async createUser(payload) {
    try {
      const response = await api.post('/admin/users', payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async updateUser(id, payload) {
    try {
      const response = await api.put(`/admin/users/${id}`, payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async deleteUser(id) {
    try {
      const response = await api.delete(`/admin/users/${id}`)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },
}
