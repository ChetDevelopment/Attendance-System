import api from './api'

const toError = (error, fallback) => {
  if (error.response?.data?.message) return error.response.data.message
  if (error.message) return error.message
  return fallback
}

export const teacherService = {
  async getDashboard() {
    try {
      const response = await api.get('/teacher/dashboard')
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to load teacher dashboard data.'))
    }
  },

  async getSchedule() {
    try {
      const response = await api.get('/teacher/schedule')
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to load schedule data.'))
    }
  },

  async getJustifications() {
    try {
      const response = await api.get('/teacher/justifications')
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to load justifications.'))
    }
  },

  async getHistory() {
    try {
      const response = await api.get('/teacher/history')
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to load history.'))
    }
  },

  async getStudents() {
    try {
      const response = await api.get('/teacher/students')
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to load students.'))
    }
  },

  async getNotifications() {
    try {
      const response = await api.get('/teacher/notifications')
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to load notifications.'))
    }
  },

  async getGoogleCalendarEvents(calendarId = 'vichet.sat@student.passerellesnumeriques.org') {
    try {
      // Use the Google Calendar API endpoint directly
      const apiKey = import.meta.env.VITE_GOOGLE_API_KEY
      if (!apiKey) {
        return { items: [], error: 'Google Calendar API key not configured. Please add VITE_GOOGLE_API_KEY to your .env file.' }
      }
      const response = await fetch(
        `https://www.googleapis.com/calendar/v3/calendars/${encodeURIComponent(calendarId)}/events?key=${apiKey}`
      )
      
      if (!response.ok) {
        throw new Error(`Google Calendar API error: ${response.status}`)
      }
      
      const data = await response.json()
      return data
    } catch (error) {
      throw new Error(toError(error, 'Failed to load Google Calendar events.'))
    }
  },
}
