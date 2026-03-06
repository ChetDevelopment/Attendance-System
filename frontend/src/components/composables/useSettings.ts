import { ref } from 'vue'

export const darkMode = ref(false)
export const language = ref('English')

const DICTIONARY: Record<string, string> = {
  dashboard: 'Dashboard',
  schedule: 'Schedule',
  attendance: 'Attendance',
  history: 'History',
  management: 'Management',
  messages: 'Messages',
  welcome: 'Welcome',
  today_is: 'Today is',
}

export const t = (key: string) => DICTIONARY[key] || key

export const useSettings = () => ({
  darkMode,
  language,
})
