import api from './api'

const toError = (error, fallback) => {
  if (error.response?.data?.message) return error.response.data.message
  if (error.message) return error.message
  return fallback
}

const normalizeAcademicYears = (payload) => {
  const rows = Array.isArray(payload?.academic_years)
    ? payload.academic_years
    : Array.isArray(payload)
      ? payload
      : []

  return rows
    .map((year) => {
      const id = Number(year?.id || 0)
      const derivedName = year?.name
        || (year?.start_year && year?.end_year ? `${year.start_year}-${year.end_year}` : '')
        || (year?.start_date && year?.end_date
          ? `${String(year.start_date).slice(0, 4)}-${String(year.end_date).slice(0, 4)}`
          : '')

      return {
        ...year,
        id,
        name: derivedName || `Academic Year ${id || ''}`.trim(),
        is_active: Boolean(year?.is_active),
      }
    })
    .filter((year) => year.id > 0)
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

  async getSchedule(params = {}) {
    try {
      const response = await api.get('/teacher/schedule', { params })
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

  async getStudents(classId = null, academicYearId = null) {
    try {
      if (classId) {
        const response = await api.get(`/teacher/classes/${classId}/students`)
        // backend returns { class_id, students }
        return response.data.students ?? response.data
      }

      // Fallback: try a general endpoint if available
      try {
        const params = academicYearId ? { academic_year_id: academicYearId } : {}
        const response = await api.get('/teacher/students', { params })
        return response.data
      } catch (err) {
        // If no general endpoint exists, return empty array instead of throwing
        return []
      }
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

  async getAcademicYears() {
    try {
      const response = await api.get('/teacher/academic-years')
      return normalizeAcademicYears(response.data)
    } catch (error) {
      throw new Error(toError(error, 'Failed to load academic years.'))
    }
  },

  async getTodaySchedule(params = {}) {
    try {
      const response = await api.get('/teacher/today-schedule', { params })
      return response.data
    } catch (error) {
      throw new Error(toError(error, 'Failed to load today\'s schedule from timetable API.'))
    }
  },
}
