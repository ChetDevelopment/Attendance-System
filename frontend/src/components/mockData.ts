import { Student } from './types'

export interface MockClass {
  id: string
  subject: string
  classCode: string
  room: string
  startTime: string
  endTime: string
}

export interface HistoryEntry {
  id: number
  date: string
  subject: string
  classCode: string
  startTime: string
  endTime: string
  presentCount: number
  absentCount: number
  lateCount: number
  attendanceRate: number
  totalStudents: number
}

export const MOCK_STUDENTS: Student[] = [
  { id: 'PNC2026-001', name: 'Sokha Dara', avatar: 'https://picsum.photos/seed/pnc001/120/120' },
  { id: 'PNC2026-002', name: 'Chantha Vuthy', avatar: 'https://picsum.photos/seed/pnc002/120/120' },
  { id: 'PNC2026-003', name: 'Nita Srey', avatar: 'https://picsum.photos/seed/pnc003/120/120' },
  { id: 'PNC2026-004', name: 'Kim Leng', avatar: 'https://picsum.photos/seed/pnc004/120/120' },
  { id: 'PNC2026-005', name: 'Malis Ka', avatar: 'https://picsum.photos/seed/pnc005/120/120' },
  { id: 'PNC2026-006', name: 'Ratha Noun', avatar: 'https://picsum.photos/seed/pnc006/120/120' },
  { id: 'PNC2026-007', name: 'Pich Mona', avatar: 'https://picsum.photos/seed/pnc007/120/120' },
  { id: 'PNC2026-008', name: 'Vannak Soeun', avatar: 'https://picsum.photos/seed/pnc008/120/120' },
]

export const MOCK_CLASSES: MockClass[] = [
  {
    id: 'CLS-01',
    subject: 'English Writing',
    classCode: 'ENG-10A',
    room: 'A-201',
    startTime: '08:00',
    endTime: '09:30',
  },
]

export const MOCK_HISTORY: HistoryEntry[] = [
  {
    id: 1,
    date: '2026-02-24',
    subject: 'English Writing',
    classCode: 'ENG-10A',
    startTime: '08:00',
    endTime: '09:30',
    presentCount: 27,
    absentCount: 3,
    lateCount: 2,
    attendanceRate: 84,
    totalStudents: 32,
  },
  {
    id: 2,
    date: '2026-02-23',
    subject: 'English Reading',
    classCode: 'ENG-10B',
    startTime: '10:00',
    endTime: '11:30',
    presentCount: 29,
    absentCount: 2,
    lateCount: 1,
    attendanceRate: 91,
    totalStudents: 32,
  },
]
