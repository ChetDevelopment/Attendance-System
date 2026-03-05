// Types for AttendX
export interface Student {
  id: string;
  name: string;
  avatar: string;
}

export type View = 'dashboard' | 'attendance' | 'history' | 'settings';

export interface AttendanceRecord {
  id: string;
  courseName: string;
  instructor: string;
  date: string;
  timeSlot: string;
  status: 'PRESENT' | 'LATE' | 'ABSENT';
  reason?: string;
  photoProof?: string; // Base64 image
  type: 'WEBCAM' | 'QR' | 'MANUAL';
}

export interface ManualRequest {
  id: string;
  studentId: string;
  studentName: string;
  courseName: string;
  reason: string;
  status: 'PENDING' | 'APPROVED' | 'REJECTED';
  createdAt: string;
}

export const MOCK_STUDENT: Student = {
  id: '2024001',
  name: 'Alex Johnson',
  avatar: 'https://picsum.photos/seed/alex/200/200',
};

export const MOCK_RECORDS: AttendanceRecord[] = [
  {
    id: '1',
    courseName: 'Web Development II',
    instructor: 'Sarah Miller',
    date: 'Oct 24, 2023',
    timeSlot: '09:00 - 10:30',
    status: 'PRESENT',
    type: 'WEBCAM',
  },
  {
    id: '2',
    courseName: 'Data Structures',
    instructor: 'Dr. Alan Turing',
    date: 'Oct 24, 2023',
    timeSlot: '11:00 - 12:30',
    status: 'LATE',
    reason: 'Transit Delay',
    type: 'MANUAL',
  },
  {
    id: '3',
    courseName: 'Artificial Intelligence',
    instructor: 'Prof. Emily Rose',
    date: 'Oct 23, 2023',
    timeSlot: '14:00 - 15:30',
    status: 'ABSENT',
    reason: 'Medical Appointment',
    type: 'MANUAL',
  },
  {
    id: '4',
    courseName: 'Quantum Computing',
    instructor: 'Dr. Richard F.',
    date: 'Oct 22, 2023',
    timeSlot: '10:00 - 12:00',
    status: 'PRESENT',
    type: 'QR',
  },
];
