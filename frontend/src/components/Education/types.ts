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
