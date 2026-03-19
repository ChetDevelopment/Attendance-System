<template>
  <div class="reports-page">
    <div class="page-header">
      <h1>Attendance Reports</h1>
      <div class="header-actions">
        <select v-model="reportType" class="report-type-select">
          <option value="student">Student Report</option>
          <option value="class">Class Report</option>
        </select>
      </div>
    </div>

    <!-- Student Report Section -->
    <div v-if="reportType === 'student'" class="report-section">
      <div class="filters">
        <div class="filter-group">
          <label>Student</label>
          <select v-model="selectedStudent" @change="loadStudentReport">
            <option value="">Select Student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.fullname }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Period</label>
          <select v-model="periodType" @change="loadStudentReport">
            <option value="all">All Time</option>
            <option value="year">By Year</option>
            <option value="month">By Month</option>
          </select>
        </div>
        <div v-if="periodType === 'year'" class="filter-group">
          <label>Year</label>
          <select v-model="selectedYear" @change="loadStudentReport">
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
        <div v-if="periodType === 'month'" class="filter-group">
          <label>Month</label>
          <select v-model="selectedMonth" @change="loadStudentReport">
            <option v-for="month in months" :key="month.value" :value="month.value">
              {{ month.label }}
            </option>
          </select>
        </div>
        <div v-if="periodType === 'month'" class="filter-group">
          <label>Year</label>
          <select v-model="selectedYear" @change="loadStudentReport">
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
        <button @click="exportStudentReport" class="btn-export" :disabled="!selectedStudent">
          Export to Excel
        </button>
      </div>

      <!-- Report Content -->
      <div v-if="studentReport" class="report-content">
        <div class="report-header">
          <h2>{{ studentReport.student?.name }}</h2>
          <p class="class-info">Class: {{ studentReport.student?.class || 'N/A' }}</p>
        </div>
        
        <div class="summary-cards">
          <div class="summary-card">
            <div class="card-value">{{ studentReport.summary?.total_sessions || 0 }}</div>
            <div class="card-label">Total Sessions</div>
          </div>
          <div class="summary-card present">
            <div class="card-value">{{ studentReport.summary?.present || 0 }}</div>
            <div class="card-label">Present</div>
          </div>
          <div class="summary-card absent">
            <div class="card-value">{{ studentReport.summary?.absent || 0 }}</div>
            <div class="card-label">Absent</div>
          </div>
          <div class="summary-card late">
            <div class="card-value">{{ studentReport.summary?.late || 0 }}</div>
            <div class="card-label">Late</div>
          </div>
          <div class="summary-card percentage">
            <div class="card-value">{{ studentReport.summary?.attendance_percentage || 0 }}%</div>
            <div class="card-label">Attendance</div>
          </div>
        </div>

        <!-- Monthly Breakdown (for yearly view) -->
        <div v-if="periodType === 'year' && studentReport.monthly_breakdown" class="breakdown-section">
          <h3>Monthly Breakdown</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Total</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Late</th>
                <th>Percentage</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="month in studentReport.monthly_breakdown" :key="month.month">
                <td>{{ month.month_name }}</td>
                <td>{{ month.total_sessions }}</td>
                <td>{{ month.present }}</td>
                <td>{{ month.absent }}</td>
                <td>{{ month.late }}</td>
                <td>{{ month.attendance_percentage }}%</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Daily Breakdown (for monthly view) -->
        <div v-if="periodType === 'month' && studentReport.daily_breakdown" class="breakdown-section">
          <h3>Daily Breakdown</h3>
          <div v-for="day in studentReport.daily_breakdown" :key="day.date" class="day-card">
            <div class="day-header">{{ day.date }}</div>
            <div class="day-sessions">
              <div v-for="session in day.sessions" :key="session.session_id" class="session-item">
                <span class="session-name">{{ session.session_name }}</span>
                <span :class="['status-badge', session.status?.toLowerCase()]">{{ session.status }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Class Report Section -->
    <div v-if="reportType === 'class'" class="report-section">
      <div class="filters">
        <div class="filter-group">
          <label>Class</label>
          <select v-model="selectedClass" @change="loadClassReport">
            <option value="">Select Class</option>
            <option v-for="cls in classes" :key="cls.id" :value="cls.id">
              {{ cls.class_name }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Period</label>
          <select v-model="classPeriodType" @change="loadClassReport">
            <option value="all">All Time</option>
            <option value="month">By Month</option>
          </select>
        </div>
        <div v-if="classPeriodType === 'month'" class="filter-group">
          <label>Month</label>
          <select v-model="selectedMonth" @change="loadClassReport">
            <option v-for="month in months" :key="month.value" :value="month.value">
              {{ month.label }}
            </option>
          </select>
        </div>
        <div v-if="classPeriodType === 'month'" class="filter-group">
          <label>Year</label>
          <select v-model="selectedYear" @change="loadClassReport">
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
        <button @click="exportClassReport" class="btn-export" :disabled="!selectedClass">
          Export to Excel
        </button>
      </div>

      <!-- Report Content -->
      <div v-if="classReport" class="report-content">
        <div class="report-header">
          <h2>{{ classReport.class?.name }}</h2>
          <p class="class-info">Total Students: {{ classReport.class?.total_students || 0 }}</p>
        </div>
        
        <div class="summary-cards">
          <div class="summary-card">
            <div class="card-value">{{ classReport.summary?.total_attendance_records || 0 }}</div>
            <div class="card-label">Total Records</div>
          </div>
          <div class="summary-card present">
            <div class="card-value">{{ classReport.summary?.present || 0 }}</div>
            <div class="card-label">Present</div>
          </div>
          <div class="summary-card absent">
            <div class="card-value">{{ classReport.summary?.absent || 0 }}</div>
            <div class="card-label">Absent</div>
          </div>
          <div class="summary-card late">
            <div class="card-value">{{ classReport.summary?.late || 0 }}</div>
            <div class="card-label">Late</div>
          </div>
          <div class="summary-card percentage">
            <div class="card-value">{{ classReport.summary?.attendance_percentage || 0 }}%</div>
            <div class="card-label">Attendance</div>
          </div>
        </div>

        <!-- Session Summary (for monthly view) -->
        <div v-if="classPeriodType === 'month' && classReport.session_summary" class="breakdown-section">
          <h3>Session Breakdown</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Session</th>
                <th>Total</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Late</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="session in classReport.session_summary" :key="session.session_id">
                <td>{{ session.session_name }}</td>
                <td>{{ session.total }}</td>
                <td>{{ session.present }}</td>
                <td>{{ session.absent }}</td>
                <td>{{ session.late }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Recent Sessions -->
        <div v-if="!classPeriodType || classPeriodType === 'all'" class="breakdown-section">
          <h3>Recent Sessions</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Session</th>
                <th>Total</th>
                <th>Present</th>
                <th>Absent</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="session in classReport.recent_sessions" :key="session.session_id">
                <td>{{ session.session_id }}</td>
                <td>{{ session.total }}</td>
                <td>{{ session.present }}</td>
                <td>{{ session.absent }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading report...</p>
    </div>
  </div>
</template>

<script>
import reportService from '../services/reportService';
import { studentService } from '../services/studentService';
import { adminAcademicService } from '../services/adminAcademicService';

export default {
  name: 'ReportsPage',
  data() {
    return {
      reportType: 'student',
      selectedStudent: '',
      selectedClass: '',
      periodType: 'all',
      classPeriodType: 'all',
      selectedYear: new Date().getFullYear(),
      selectedMonth: new Date().getMonth() + 1,
      students: [],
      classes: [],
      studentReport: null,
      classReport: null,
      loading: false,
      years: [],
      months: [
        { value: 1, label: 'January' },
        { value: 2, label: 'February' },
        { value: 3, label: 'March' },
        { value: 4, label: 'April' },
        { value: 5, label: 'May' },
        { value: 6, label: 'June' },
        { value: 7, label: 'July' },
        { value: 8, label: 'August' },
        { value: 9, label: 'September' },
        { value: 10, label: 'October' },
        { value: 11, label: 'November' },
        { value: 12, label: 'December' }
      ]
    };
  },
  created() {
    this.initYears();
    this.loadStudents();
    this.loadClasses();
  },
  methods: {
    initYears() {
      const currentYear = new Date().getFullYear();
      this.years = [currentYear, currentYear - 1, currentYear - 2, currentYear - 3];
    },
    async loadStudents() {
      try {
        const response = await studentService.getStudents();
        this.students = response?.data || [];
      } catch (error) {
        console.error('Error loading students:', error);
      }
    },
    async loadClasses() {
      try {
        const response = await adminAcademicService.getClasses();
        this.classes = response?.data || [];
      } catch (error) {
        console.error('Error loading classes:', error);
      }
    },
    async loadStudentReport() {
      if (!this.selectedStudent) return;
      
      this.loading = true;
      try {
        let response;
        if (this.periodType === 'all') {
          response = await reportService.getStudentReport(this.selectedStudent);
        } else if (this.periodType === 'year') {
          response = await reportService.getStudentReportByYear(this.selectedStudent, this.selectedYear);
        } else if (this.periodType === 'month') {
          response = await reportService.getStudentReportByMonth(this.selectedStudent, this.selectedMonth, this.selectedYear);
        }
        this.studentReport = response.data.data;
      } catch (error) {
        console.error('Error loading student report:', error);
        this.studentReport = null;
      } finally {
        this.loading = false;
      }
    },
    async loadClassReport() {
      if (!this.selectedClass) return;
      
      this.loading = true;
      try {
        let response;
        if (this.classPeriodType === 'all') {
          response = await reportService.getClassReport(this.selectedClass);
        } else if (this.classPeriodType === 'month') {
          response = await reportService.getClassMonthlySummary(this.selectedClass, this.selectedMonth, this.selectedYear);
        }
        this.classReport = response.data.data;
      } catch (error) {
        console.error('Error loading class report:', error);
        this.classReport = null;
      } finally {
        this.loading = false;
      }
    },
    async exportStudentReport() {
      if (!this.selectedStudent) return;
      
      try {
        const response = await reportService.exportStudentReport(this.selectedStudent, {
          year: this.selectedYear,
          month: this.periodType === 'month' ? this.selectedMonth : null
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `student_attendance_${this.selectedStudent}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
      } catch (error) {
        console.error('Error exporting student report:', error);
      }
    },
    async exportClassReport() {
      if (!this.selectedClass) return;
      
      try {
        const response = await reportService.exportClassReport(this.selectedClass, {
          year: this.selectedYear,
          month: this.classPeriodType === 'month' ? this.selectedMonth : null
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `class_attendance_${this.selectedClass}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
      } catch (error) {
        console.error('Error exporting class report:', error);
      }
    }
  }
};
</script>

<style scoped>
.reports-page {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h1 {
  font-size: 24px;
  font-weight: bold;
  color: #333;
}

.report-type-select {
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.report-section {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filters {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.filter-group label {
  font-size: 12px;
  color: #666;
  font-weight: 500;
}

.filter-group select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.btn-export {
  padding: 8px 16px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  align-self: flex-end;
}

.btn-export:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.btn-export:hover:not(:disabled) {
  background: #059669;
}

.report-header {
  margin-bottom: 20px;
}

.report-header h2 {
  font-size: 20px;
  font-weight: bold;
  color: #333;
}

.class-info {
  color: #666;
  font-size: 14px;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.summary-card {
  background: #f9fafb;
  padding: 15px;
  border-radius: 8px;
  text-align: center;
}

.summary-card.present {
  background: #d1fae5;
}

.summary-card.absent {
  background: #fee2e2;
}

.summary-card.late {
  background: #fef3c7;
}

.summary-card.percentage {
  background: #dbeafe;
}

.card-value {
  font-size: 28px;
  font-weight: bold;
  color: #333;
}

.card-label {
  font-size: 12px;
  color: #666;
  margin-top: 5px;
}

.breakdown-section {
  margin-top: 20px;
}

.breakdown-section h3 {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 10px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.data-table th {
  background: #f9fafb;
  font-weight: 600;
  color: #333;
}

.data-table td {
  color: #666;
}

.day-card {
  background: #f9fafb;
  padding: 10px;
  margin-bottom: 10px;
  border-radius: 4px;
}

.day-header {
  font-weight: bold;
  margin-bottom: 5px;
}

.session-item {
  display: flex;
  justify-content: space-between;
  padding: 5px 0;
}

.status-badge {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.status-badge.present,
.status-badge.Present {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.absent,
.status-badge.Absent {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.late,
.status-badge.Late {
  background: #fef3c7;
  color: #92400e;
}

.loading {
  text-align: center;
  padding: 40px;
}

.spinner {
  border: 3px solid #f3f3f3;
  border-top: 3px solid #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
