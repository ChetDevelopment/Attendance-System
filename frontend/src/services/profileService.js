import api from './api'

const formatError = (error) => {
  if (error.response?.status === 422) {
    return {
      message: error.response.data?.message || 'Validation failed.',
      errors: error.response.data?.errors || {},
    }
  }

  return {
    message: error.response?.data?.message || error.message || 'Profile request failed.',
    errors: {},
  }
}

export const profileService = {
  async getProfile() {
    try {
      const response = await api.get('/user/profile')
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async updateProfile(payload) {
    try {
      const response = await api.post('/user/profile', payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async updateSettings(payload) {
    try {
      const response = await api.post('/user/settings', payload)
      return response.data
    } catch (error) {
      throw formatError(error)
    }
  },

  async uploadAvatar(file) {
    try {
      const formData = new FormData()
      formData.append('avatar', file)

      const response = await api.post('/user/profile/avatar', formData, {
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
