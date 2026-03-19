import api from './api'

const normalizeError = (error) => ({
  message: error.response?.data?.message || error.message || 'Backup request failed.',
  errors: error.response?.data?.errors || {},
})

export const backupAdminService = {
  async list() {
    try {
      const response = await api.get('/admin/system/backups')
      return response.data
    } catch (error) {
      throw normalizeError(error)
    }
  },

  async create() {
    try {
      const response = await api.post('/admin/system/backups')
      return response.data
    } catch (error) {
      throw normalizeError(error)
    }
  },

  async restore(name) {
    try {
      const response = await api.post('/admin/system/backups/restore', { name })
      return response.data
    } catch (error) {
      throw normalizeError(error)
    }
  },
}
