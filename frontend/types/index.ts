export type AttendanceStatus = 'PRESENT' | 'LATE' | 'ABSENT'

export interface AttendanceRecord {
  id: string
  studentId: string
  courseName: string
  instructor: string
  date: string
  timeSlot: string
  status: AttendanceStatus
  photoProof?: string
  type?: string
}

export const MOCK_STUDENT = {
  id: 'STU-001',
  name: 'Student',
  avatar: 'https://i.pravatar.cc/300',
}

