export type AttendanceStatus = 'PRESENT' | 'ABSENT' | 'LATE' | 'PENDING'
export type AttendanceMethod = 'MANUAL' | 'QR' | 'FACE'

export interface Student {
  id: string
  name: string
  avatar: string
}

export interface AttendanceRecord {
  id: string
  studentId: string
  status: AttendanceStatus
  method?: AttendanceMethod
  timestamp?: string
  date?: string
  timeSlot?: string
  courseName?: string
}
