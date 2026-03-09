export interface TimetableEntry {
  id: number
  day: number
  teacher: string
  subject: string
  classCode: string
  room?: string
  startHour: number
  duration: number
}

export const TIMETABLE_DATA: TimetableEntry[] = [
  { id: 1, day: 0, teacher: 'Sovanchansreyleap', subject: 'English Writing', classCode: '10A-ENG', room: 'R101', startHour: 8, duration: 1.5 },
  { id: 2, day: 0, teacher: 'Dr. Smith', subject: 'Mathematics', classCode: '11B-MATH', room: 'R203', startHour: 10, duration: 1.5 },
  { id: 3, day: 1, teacher: 'Sovanchansreyleap', subject: 'English Reading', classCode: '10B-ENG', room: 'R109', startHour: 9, duration: 1.5 },
  { id: 4, day: 2, teacher: 'Dr. Smith', subject: 'Advanced Algebra', classCode: '12A-MATH', room: 'Lab-2', startHour: 13, duration: 1.5 },
  { id: 5, day: 3, teacher: 'Sovanchansreyleap', subject: 'Communication Skills', classCode: '9A-ENG', room: 'R305', startHour: 8, duration: 1.5 },
  { id: 6, day: 4, teacher: 'Dr. Smith', subject: 'Calculus', classCode: '12B-MATH', room: 'Lab-1', startHour: 14, duration: 2 },
]

export const getCurrentAndNextSession = (
  now: Date,
  teacherName: string
): { active?: TimetableEntry; nextToday?: TimetableEntry } => {
  const day = now.getDay() - 1
  const normalizedDay = day < 0 ? 6 : day
  const currentHour = now.getHours() + now.getMinutes() / 60

  const todaySessions = TIMETABLE_DATA
    .filter((entry) => entry.day === normalizedDay && entry.teacher === teacherName)
    .sort((a, b) => a.startHour - b.startHour)

  const active = todaySessions.find(
    (entry) => currentHour >= entry.startHour && currentHour < entry.startHour + entry.duration
  )

  const nextToday = todaySessions.find((entry) => entry.startHour > currentHour)

  return { active, nextToday }
}
