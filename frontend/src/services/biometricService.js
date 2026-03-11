import api from './api';

// Biometric API service methods
export const biometricService = {
  // Card scanning
  async scanCard(sessionId, cardData) {
    try {
      const response = await api.post('/student/attendance/card-scan', {
        sessionId,
        cardData
      });
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Card scan failed');
    }
  },

  // Fingerprint scanning
  async scanFingerprint(sessionId, fingerprintData) {
    try {
      const response = await api.post('/student/attendance/fingerprint-scan', {
        sessionId,
        fingerprintData
      });
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Fingerprint scan failed');
    }
  },

  // Biometric validation
  async validateBiometricScan(sessionId, scanType, scanData) {
    try {
      const response = await api.post('/student/attendance/validate-biometric', {
        sessionId,
        scanType,
        scanData
      });
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Biometric validation failed');
    }
  },

  // Get biometric scan history
  async getBiometricHistory() {
    try {
      const response = await api.get('/student/attendance/biometric-history');
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to fetch biometric history');
    }
  },

  // Get biometric system status
  async getSystemStatus() {
    try {
      const response = await api.get('/student/attendance/biometric-status');
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to fetch system status');
    }
  },

  // Get student info after biometric scan
  async getStudentInfoAfterScan(scanType, scanData) {
    try {
      const response = await api.post('/student/attendance/student-info', {
        scanType,
        scanData
      });
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Failed to fetch student info');
    }
  }
};