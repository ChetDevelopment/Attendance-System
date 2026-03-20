const TOKEN_KEY = 'access_token'
const STUDENT_SESSION_KEY = 'student_session'
const USER_KEY = 'auth_user'
const USER_ROLE_KEY = 'auth_user_role'

export const getToken = () => localStorage.getItem(TOKEN_KEY)

export const setToken = (token) => {
  localStorage.setItem(TOKEN_KEY, token)
}

export const clearToken = () => {
  localStorage.removeItem(TOKEN_KEY)
}

export const getStudentSession = () => localStorage.getItem(STUDENT_SESSION_KEY)

export const setStudentSession = (idCard) => {
  localStorage.setItem(STUDENT_SESSION_KEY, idCard)
}

export const clearStudentSession = () => {
  localStorage.removeItem(STUDENT_SESSION_KEY)
}

export const getUser = () => {
  const raw = localStorage.getItem(USER_KEY)

  if (!raw) return null

  try {
    return JSON.parse(raw)
  } catch {
    localStorage.removeItem(USER_KEY)
    return null
  }
}

export const setUser = (user) => {
  if (!user) return
  localStorage.setItem(USER_KEY, JSON.stringify(user))
}

export const clearUser = () => {
  localStorage.removeItem(USER_KEY)
}

export const normalizeRole = (role) => {
  return typeof role === 'string' ? role.trim().toLowerCase() : ''
}

export const resolveUserRole = (user) => {
  if (!user) return ''

  // First, check for role object with name or slug
  if (typeof user.role === 'object' && user.role) {
    const roleName = user.role.name ? normalizeRole(user.role.name) : ''
    const roleSlug = user.role.slug ? normalizeRole(user.role.slug) : ''

    // Try name first
    if (roleName) {
      if (roleName === 'admin') return 'admin'
      if (roleName === 'teacher') return 'teacher'
      if (roleName === 'education team') return 'education'
      if (roleName === 'training team') return 'training'
      if (roleName === 'student') return 'student'
    }

    // Try slug
    if (roleSlug) {
      if (roleSlug === 'admin') return 'admin'
      if (roleSlug === 'teacher') return 'teacher'
      if (roleSlug === 'education_team') return 'education'
      if (roleSlug === 'training_team') return 'training'
      if (roleSlug === 'student') return 'student'
    }
  }

  // Fallback: check for role as string
  const roleValue =
    typeof user.role === 'string'
      ? user.role
      : ''

  const directRole = normalizeRole(roleValue)
  if (directRole) {
    if (directRole === 'admin') return 'admin'
    if (directRole === 'teacher') return 'teacher'
    if (directRole === 'education_team') return 'education'
    if (directRole === 'training_team') return 'training'
    if (directRole === 'student') return 'student'
    return directRole
  }

  // Check for roles array (legacy)
  if (Array.isArray(user.roles) && user.roles.length) {
    const firstRole = user.roles[0]
    return normalizeRole(typeof firstRole === 'string' ? firstRole : firstRole?.name)
  }

  // Check for permissions (fallback)
  if (Array.isArray(user.permissions)) {
    const hasTeacherPermission = user.permissions.some((permission) => {
      const name = typeof permission === 'string' ? permission : permission?.name
      return normalizeRole(name).includes('teacher')
    })

    if (hasTeacherPermission) return 'teacher'
  }

  return ''
}

export const getUserRole = () => {
  // Check for cached role first
  const storedRole = normalizeRole(localStorage.getItem(USER_ROLE_KEY))
  if (storedRole) return storedRole

  // Resolve role from user data and cache it
  const roleFromUser = resolveUserRole(getUser())
  if (roleFromUser) {
    localStorage.setItem(USER_ROLE_KEY, roleFromUser)
  }
  return roleFromUser
}

export const setUserRole = (role) => {
  const normalizedRole = normalizeRole(role)

  if (!normalizedRole) {
    localStorage.removeItem(USER_ROLE_KEY)
    return
  }

  localStorage.setItem(USER_ROLE_KEY, normalizedRole)
}

export const clearUserRole = () => {
  localStorage.removeItem(USER_ROLE_KEY)
}

import api from './api';

export const clearAllAuthData = () => {
  clearToken()
  clearStudentSession()
  clearUser()
  clearUserRole()
}

export const logout = async () => {
  try {
    await api.post('/auth/logout')
  } catch (error) {
    console.warn('Logout API call failed, proceeding with local cleanup:', error)
  } finally {
    clearAllAuthData()
  }
}

export const hasSession = () => Boolean(getToken() || getStudentSession())
