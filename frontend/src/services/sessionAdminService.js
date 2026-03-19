import api from './api'

const formatError = (error) => {
  if (error.response?.status === 422) {
    return {
      message: error.response.data?.message || 'Validation failed.',
      errors: error.response.data?.errors || {},
    }
  }

  return {
    message: error.response?.data?.message || error.message || 'Session request failed.',
    errors: {},
  }
}

export const sessionAdminService = {
  async list(params = {}) {
    try {
      const response = await api.get('/admin/sessions', { params })
      return Array.isArray(response.data?.data) ? response.data.data : []
    } catch (error) {
      throw formatError(error)
    }
  },

  async get(id) {
    try {
      const response = await api.get(`/admin/sessions/${id}`)
      return response.data?.data ?? null
    } catch (error) {
      throw formatError(error)
    }
  },

  async create(payload) {
    try {
      const response = await api.post('/admin/sessions', payload)
      return response.data?.data ?? null
    } catch (error) {
      throw formatError(error)
    }
  },

  async update(id, payload) {
    try {
      const response = await api.patch(`/admin/sessions/${id}`, payload)
      return response.data?.data ?? null
    } catch (error) {
      throw formatError(error)
    }
  },

  async delete(id) {
    try {
      const response = await api.delete(`/admin/sessions/${id}`)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async toggle(id) {
    try {
      const response = await api.post(`/admin/sessions/${id}/toggle`)
      return response.data?.data ?? null
    } catch (error) {
      throw formatError(error)
    }
  },

  async initialize(payload) {
    try {
      const response = await api.post('/admin/sessions/initialize', payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },
}
