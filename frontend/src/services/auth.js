import { computed, ref } from 'vue'
import api from './api';

const TOKEN_KEY = 'access_token'
const STUDENT_SESSION_KEY = 'student_session'
const USER_KEY = 'auth_user'
const USER_ROLE_KEY = 'auth_user_role'

const userState = ref(null)
const roleState = ref('')

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
  if (userState.value) return userState.value

  const raw = localStorage.getItem(USER_KEY)
  if (!raw) return null

  try {
    userState.value = JSON.parse(raw)
    return userState.value
  } catch {
    localStorage.removeItem(USER_KEY)
    userState.value = null
    return null
  }
}

export const setUser = (user) => {
  if (!user) return
  userState.value = user
  localStorage.setItem(USER_KEY, JSON.stringify(user))
}

export const clearUser = () => {
  userState.value = null
  localStorage.removeItem(USER_KEY)
}

export const normalizeRole = (role) => {
  return typeof role === 'string' ? role.trim().toLowerCase() : ''
}

export const resolveUserRole = (user) => {
  if (!user) return ''

  if (typeof user.role === 'object' && user.role) {
    const roleName = user.role.name ? normalizeRole(user.role.name) : ''
    const roleSlug = user.role.slug ? normalizeRole(user.role.slug) : ''

    if (roleName) {
      if (roleName === 'admin') return 'admin'
      if (roleName === 'teacher') return 'teacher'
      if (roleName === 'education team') return 'education'
      if (roleName === 'training team') return 'training'
      if (roleName === 'student') return 'student'
    }

    if (roleSlug) {
      if (roleSlug === 'admin') return 'admin'
      if (roleSlug === 'teacher') return 'teacher'
      if (roleSlug === 'education_team') return 'education'
      if (roleSlug === 'training_team') return 'training'
      if (roleSlug === 'student') return 'student'
    }
  }

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

  if (Array.isArray(user.roles) && user.roles.length) {
    const firstRole = user.roles[0]
    return normalizeRole(typeof firstRole === 'string' ? firstRole : firstRole?.name)
  }

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
  if (roleState.value) return roleState.value

  const storedRole = normalizeRole(localStorage.getItem(USER_ROLE_KEY))
  if (storedRole) {
    roleState.value = storedRole
    return storedRole
  }

  const roleFromUser = resolveUserRole(getUser())
  if (roleFromUser) {
    roleState.value = roleFromUser
    localStorage.setItem(USER_ROLE_KEY, roleFromUser)
  }
  return roleFromUser
}

export const setUserRole = (role) => {
  const normalizedRole = normalizeRole(role)

  if (!normalizedRole) {
    roleState.value = ''
    localStorage.removeItem(USER_ROLE_KEY)
    return
  }

  roleState.value = normalizedRole
  localStorage.setItem(USER_ROLE_KEY, normalizedRole)
}

export const clearUserRole = () => {
  roleState.value = ''
  localStorage.removeItem(USER_ROLE_KEY)
}

export const clearAllAuthData = () => {
  clearToken()
  clearStudentSession()
  clearUser()
  clearUserRole()
}

export const studentProfile = computed(() => {
  const user = getUser() || {}
  const linkedStudent = user.student || {}

  return {
    id: linkedStudent.student_code || user.student_id || linkedStudent.id || user.id || user.username || 'N/A',
    name: linkedStudent.fullname || user.fullname || user.name || linkedStudent.username || user.username || 'Student',
    // Prefer the current user avatar first so navbar/profile updates reflect
    // the latest uploaded image immediately, even if student.profile is stale.
    avatar: user.avatar || user.avatar_url || linkedStudent.profile || user.profile_photo_url || 'https://api.dicebear.com/7.x/avataaars/svg?seed=student',
    email: linkedStudent.email || user.email || '',
    className: linkedStudent.class_name || '',
  }
})

export const updateProfile = (name, avatar) => {
  const currentUser = getUser()
  if (!currentUser) return null

  const updatedUser = {
    ...currentUser,
    fullname: name,
    name,
    avatar,
  }

  setUser(updatedUser)
  return updatedUser
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
