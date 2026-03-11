export interface TeacherJustification {
  id: string
  studentName: string
  studentId: string
  studentPhoto: string
  classCode: string
  subject: string
  educationComment: string
  date: string
  timestamp: string
  teacher: string
}

const JUSTIFICATIONS: TeacherJustification[] = [
  {
    id: 'j-101',
    studentName: 'Sat Vichet',
    studentId: 'PNC2026-053',
    studentPhoto: 'https://i.pravatar.cc/80?img=18',
    classCode: '10A-MATH',
    subject: 'Mathematics',
    educationComment: 'Medical appointment validated by parents and school office.',
    date: '2026-02-26',
    timestamp: '2026-02-26 08:42 AM',
    teacher: 'Sovanchansreyleap',
  },
  {
    id: 'j-102',
    studentName: 'Lara Croft',
    studentId: 'PNC2026-124',
    studentPhoto: 'https://i.pravatar.cc/80?img=48',
    classCode: '11B-PHY',
    subject: 'Physics',
    educationComment: 'Transport issue confirmed by dorm supervisor.',
    date: '2026-02-26',
    timestamp: '2026-02-26 11:10 AM',
    teacher: 'Dr. Smith',
  },
  {
    id: 'j-103',
    studentName: 'Nita Chan',
    studentId: 'PNC2026-077',
    studentPhoto: 'https://i.pravatar.cc/80?img=32',
    classCode: '12A-BIO',
    subject: 'Biology',
    educationComment: 'Family emergency approved as excused absence.',
    date: '2026-02-25',
    timestamp: '2026-02-25 02:05 PM',
    teacher: 'Sovanchansreyleap',
  },
]

export const getJustificationsForTeacher = (teacherName: string) => {
  return JUSTIFICATIONS.filter((item) => item.teacher === teacherName)
}
