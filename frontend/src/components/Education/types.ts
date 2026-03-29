export interface DashboardStats {
  absentToday: number
  lateToday: number
  highRisk: number
  pendingFollowUp: number
}

export interface TrendData {
  name: string
  value: number
}

export interface ClassReport {
  class: string
  present_count: number
  absent_count: number
  late_count: number
}

export type ReportPeriod = 'today' | 'weekly' | 'monthly'

export interface AcademicYearOption {
  id: number
  name: string
  is_active?: boolean
}

export interface EducationClassOption {
  id: number
  name: string
  code?: string | null
  academic_year_id?: number | null
  label: string
}

export interface EducationReportRow {
  id: number
  name: string
  student_code: string
  photo: string | null
  class_name?: string
  class_code?: string | null
  late_count: number
  absent_count: number
}
