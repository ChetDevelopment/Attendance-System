import { ref, computed } from 'vue'

const STORAGE_KEY_DARK_MODE = 'teacher_dark_mode'
const STORAGE_KEY_LANGUAGE = 'teacher_language'

export const useSettings = () => {
  const darkMode = ref<boolean>(
    localStorage.getItem(STORAGE_KEY_DARK_MODE) === 'true'
  )

  const language = ref<string>(
    localStorage.getItem(STORAGE_KEY_LANGUAGE) || 'en'
  )

  // Initialize dark mode and language from backend settings
  const initFromBackend = (theme: string, lang: string) => {
    // Only use backend values if localStorage doesn't have values
    if (!localStorage.getItem(STORAGE_KEY_DARK_MODE)) {
      darkMode.value = theme === 'dark'
      if (darkMode.value) {
        document.documentElement.classList.add('dark')
      }
    } else {
      // Apply existing localStorage value
      if (darkMode.value) {
        document.documentElement.classList.add('dark')
      }
    }
    
    if (!localStorage.getItem(STORAGE_KEY_LANGUAGE)) {
      language.value = lang || 'en'
      localStorage.setItem(STORAGE_KEY_LANGUAGE, language.value)
    }
  }

  const toggleDarkMode = () => {
    darkMode.value = !darkMode.value
    localStorage.setItem(STORAGE_KEY_DARK_MODE, String(darkMode.value))
    if (darkMode.value) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  const setLanguage = (lang: string) => {
    language.value = lang
    localStorage.setItem(STORAGE_KEY_LANGUAGE, lang)
  }

  return {
    darkMode,
    language,
    toggleDarkMode,
    setLanguage,
    initFromBackend,
  }
}

// Translation function
const translations: Record<string, Record<string, string>> = {
  en: {
    dashboard: 'Dashboard',
    schedule: 'Schedule',
    attendance: 'Attendance',
    history: 'History',
    students: 'Students',
    messages: 'Messages',
    settings: 'Settings',
    notifications: 'Notifications',
    logout: 'Logout',
    management: 'Management',
  },
  km: {
    dashboard: 'ទាំងអស់',
    schedule: 'កាលវិភាគ',
    attendance: 'វត្តមាន',
    history: 'ប្រវត្តិសាស្ត្រ',
    students: 'សិស្ស',
    messages: 'សារ',
    settings: 'ការកំណត់',
    notifications: 'ការជូនដំណឹង',
    logout: 'ចេញ',
    management: 'ការគ្រប់គ្រង',
  },
}

export const t = (key: string): string => {
  const lang = localStorage.getItem(STORAGE_KEY_LANGUAGE) || 'en'
  return translations[lang]?.[key] || translations['en']?.[key] || key
}
