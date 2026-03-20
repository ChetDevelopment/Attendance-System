import api from './api';

const reportService = {
  buildAdminPath(path) {
    return `/admin${path}`;
  },

  /**
   * Get student attendance report
   */
  getStudentReport(studentId) {
    return api.get(this.buildAdminPath(`/reports/student/${studentId}`));
  },

  /**
   * Get student attendance report by month
   */
  getStudentReportByMonth(studentId, month, year) {
    return api.get(this.buildAdminPath(`/reports/student/${studentId}/month/${month}/year/${year}`));
  },

  /**
   * Get student attendance report by year
   */
  getStudentReportByYear(studentId, year) {
    return api.get(this.buildAdminPath(`/reports/student/${studentId}/year/${year}`));
  },

  /**
   * Get class attendance report
   */
  getClassReport(classId) {
    return api.get(this.buildAdminPath(`/reports/class/${classId}`));
  },

  /**
   * Get class attendance monthly summary
   */
  getClassMonthlySummary(classId, month, year) {
    return api.get(this.buildAdminPath(`/reports/class/${classId}/month/${month}/year/${year}`));
  },

  /**
   * Get class attendance by date range
   */
  getClassReportByDateRange(classId, startDate, endDate) {
    return api.get(this.buildAdminPath(`/reports/class/${classId}/range`), {
      params: { start_date: startDate, end_date: endDate }
    });
  },

  /**
   * Get attendance records with filters
   */
  getAttendance(params = {}) {
    return api.get(this.buildAdminPath('/reports/attendance'), { params });
  },

  /**
   * Export student attendance to Excel
   */
  exportStudentReport(studentId, options = {}) {
    const params = new URLSearchParams();
    if (options.year) params.append('year', options.year);
    if (options.month) params.append('month', options.month);
    
    const queryString = params.toString();
    const url = this.buildAdminPath(`/reports/export/student/${studentId}${queryString ? '?' + queryString : ''}`);
    
    return api.get(url, { responseType: 'blob' });
  },

  /**
   * Export class attendance to Excel
   */
  exportClassReport(classId, options = {}) {
    const params = new URLSearchParams();
    if (options.year) params.append('year', options.year);
    if (options.month) params.append('month', options.month);
    
    const queryString = params.toString();
    const url = this.buildAdminPath(`/reports/export/class/${classId}${queryString ? '?' + queryString : ''}`);
    
    return api.get(url, { responseType: 'blob' });
  },

  /**
   * Export attendance by date range
   */
  exportByDateRange(startDate, endDate, options = {}) {
    const params = new URLSearchParams({
      start_date: startDate,
      end_date: endDate
    });
    
    if (options.classId) params.append('class_id', options.classId);
    if (options.studentId) params.append('student_id', options.studentId);
    
    return api.get(this.buildAdminPath(`/reports/export/range?${params.toString()}`), { responseType: 'blob' });
  },

  /**
   * Clear report cache
   */
  clearCache(type, id) {
    return api.post(this.buildAdminPath('/reports/clear-cache'), { type, id });
  }
};

export default reportService;
