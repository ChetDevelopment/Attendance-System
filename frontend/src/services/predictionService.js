import api from './api';

const predictionService = {
  /**
   * Get at-risk students
   * @param {number} threshold - Risk threshold (attendance % below this = at risk)
   */
  getAtRiskStudents(threshold = 30) {
    return api.get('/predictions/at-risk', {
      params: { threshold }
    });
  },

  /**
   * Get individual student prediction
   * @param {number} studentId - Student ID
   */
  getStudentPrediction(studentId) {
    return api.get(`/predictions/student/${studentId}`);
  },

  /**
   * Get overall system insights
   */
  getInsights() {
    return api.get('/predictions/insights');
  },

  /**
   * Get weekly prediction
   * @param {number} weekOffset - Week offset (0 = current week, 1 = next week, etc.)
   */
  getWeeklyPrediction(weekOffset = 0) {
    return api.get('/predictions/weekly', {
      params: { week_offset: weekOffset }
    });
  },

  /**
   * Get historical data analysis
   * @param {number} days - Number of days to analyze (7-90)
   */
  getHistoricalData(days = 30) {
    return api.get('/predictions/historical', {
      params: { days }
    });
  },

  /**
   * Clear prediction cache
   */
  clearCache() {
    return api.post('/predictions/clear-cache');
  }
};

export default predictionService;
