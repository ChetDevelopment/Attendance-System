const TOKEN_KEY = 'access_token'
const STUDENT_SESSION_KEY = 'student_session'
const USER_KEY = 'auth_user'
const USER_ROLE_KEY = 'auth_user_role'

const normalizeStoredString = (value) => {
  if (typeof value !== 'string') return null
  const trimmed = value.trim()
  if (!trimmed) return null

  const lowered = trimmed.toLowerCase()
  if (lowered === 'undefined' || lowered === 'null') return null

  return trimmed
}

export const getToken = () => {
  const token = normalizeStoredString(localStorage.getItem(TOKEN_KEY))
  if (!token) localStorage.removeItem(TOKEN_KEY)
  return token
}

export const setToken = (token) => {
  const normalized = normalizeStoredString(token)
  if (!normalized) {
    localStorage.removeItem(TOKEN_KEY)
    return
  }
  localStorage.setItem(TOKEN_KEY, normalized)
}

export const clearToken = () => {
  localStorage.removeItem(TOKEN_KEY)
}

export const getStudentSession = () => {
  const session = normalizeStoredString(localStorage.getItem(STUDENT_SESSION_KEY))
  if (!session) localStorage.removeItem(STUDENT_SESSION_KEY)
  return session
}

export const setStudentSession = (idCard) => {
  const normalized = normalizeStoredString(idCard)
  if (!normalized) {
    localStorage.removeItem(STUDENT_SESSION_KEY)
    return
  }
  localStorage.setItem(STUDENT_SESSION_KEY, normalized)
}

export const clearStudentSession = () => {
  localStorage.removeItem(STUDENT_SESSION_KEY)
}

export const getStoredUser = () => {
  const raw = localStorage.getItem(USER_KEY)
  if (!raw) return null
  try {
    return JSON.parse(raw)
  } catch {
    localStorage.removeItem(USER_KEY)
    return null
  }
}

export const setStoredUser = (user) => {
  if (!user) return
  localStorage.setItem(USER_KEY, JSON.stringify(user))
}

export const clearStoredUser = () => {
  localStorage.removeItem(USER_KEY)
}

export const getStoredRole = () => localStorage.getItem(USER_ROLE_KEY)

export const setStoredRole = (role) => {
  if (!role) {
    localStorage.removeItem(USER_ROLE_KEY)
    return
  }
  localStorage.setItem(USER_ROLE_KEY, role.trim().toLowerCase())
}

export const clearStoredRole = () => {
  localStorage.removeItem(USER_ROLE_KEY)
}
