<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { 
  TrendingUp, 
  TrendingDown, 
  AlertTriangle, 
  Users, 
  Calendar,
  BarChart3,
  Activity,
  RefreshCw,
  ChevronRight,
  AlertCircle,
  CheckCircle2,
  Info
} from 'lucide-vue-next';
import predictionService from '../../services/predictionService';

// Types
interface AtRiskStudent {
  student: { id: number; name: string; class: string };
  attendance_rate: number;
  risk_score: number;
  risk_level: string;
  trend: string;
  absent_sessions: number;
  total_sessions: number;
  consecutive_absences: number;
}

interface InsightData {
  summary: {
    overall_attendance_rate: number;
    at_risk_students_count: number;
    trend: string;
  };
  historical: {
    attendance_rate: number;
    trend: { last_7_days_rate: number; previous_7_days_rate: number; change: number; trend: string };
  };
  predictions: {
    expected_absences_next_week: number;
    highest_risk_days: string[];
  };
  at_risk: {
    count: number;
    critical_count: number;
    high_count: number;
  };
  recommendations: Array<{
    priority: string;
    category: string;
    message: string;
    action: string;
  }>;
}

interface WeeklyPrediction {
  week: { start_date: string; end_date: string; week_offset: number };
  predicted_absences: number;
  daily_predictions: Array<{
    day: string;
    date: string;
    expected_absences: number;
    historical_absence_rate: number;
  }>;
  highest_risk_days: string[];
}

// State
const loading = ref(false);
const activeTab = ref<'overview' | 'at-risk' | 'weekly' | 'insights'>('overview');
const error = ref('');
const insights = ref<InsightData | null>(null);
const atRiskStudents = ref<AtRiskStudent[]>([]);
const weeklyPrediction = ref<WeeklyPrediction | null>(null);
const selectedStudent = ref<AtRiskStudent | null>(null);
const studentPrediction = ref<any>(null);
const showStudentModal = ref(false);
const threshold = ref(30);

// Risk level colors
const riskLevelColors: Record<string, string> = {
  LOW: 'bg-green-100 text-green-800',
  MEDIUM: 'bg-yellow-100 text-yellow-800',
  HIGH: 'bg-orange-100 text-orange-800',
  CRITICAL: 'bg-red-100 text-red-800'
};

const priorityColors: Record<string, string> = {
  critical: 'bg-red-100 text-red-800 border-red-200',
  high: 'bg-orange-100 text-orange-800 border-orange-200',
  medium: 'bg-yellow-100 text-yellow-800 border-yellow-200',
  low: 'bg-green-100 text-green-800 border-green-200'
};

// Computed
const criticalStudents = computed(() => 
  atRiskStudents.value.filter(s => s.risk_level === 'CRITICAL')
);

const highStudents = computed(() => 
  atRiskStudents.value.filter(s => s.risk_level === 'HIGH')
);

// Methods
const loadInsights = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await predictionService.getInsights();
    insights.value = response.data.data;
  } catch (err: any) {
    error.value = err?.message || 'Failed to load insights';
  } finally {
    loading.value = false;
  }
};

const loadAtRiskStudents = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await predictionService.getAtRiskStudents(threshold.value);
    atRiskStudents.value = response.data.data.students;
  } catch (err: any) {
    error.value = err?.message || 'Failed to load at-risk students';
  } finally {
    loading.value = false;
  }
};

const loadWeeklyPrediction = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await predictionService.getWeeklyPrediction(0);
    weeklyPrediction.value = response.data.data;
  } catch (err: any) {
    error.value = err?.message || 'Failed to load weekly prediction';
  } finally {
    loading.value = false;
  }
};

const loadStudentPrediction = async (student: AtRiskStudent) => {
  selectedStudent.value = student;
  showStudentModal.value = true;
  try {
    const response = await predictionService.getStudentPrediction(student.student.id);
    studentPrediction.value = response.data.data;
  } catch (err: any) {
    error.value = err?.message || 'Failed to load student prediction';
  }
};

const refreshData = async () => {
  if (activeTab.value === 'overview' || activeTab.value === 'insights') {
    await loadInsights();
  }
  if (activeTab.value === 'at-risk') {
    await loadAtRiskStudents();
  }
  if (activeTab.value === 'weekly') {
    await loadWeeklyPrediction();
  }
};

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric' 
  });
};

const formatPercent = (value: number) => `${value.toFixed(1)}%`;

// Tab change handler
const handleTabChange = (tab: 'overview' | 'at-risk' | 'weekly' | 'insights') => {
  activeTab.value = tab;
  refreshData();
};

// Initialize
onMounted(() => {
  loadInsights();
});
</script>

<template>
  <div class="prediction-dashboard">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Attendance Predictions</h1>
        <p class="text-gray-500 mt-1">AI-powered attendance analytics and risk assessment</p>
      </div>
      <button 
        @click="refreshData" 
        :disabled="loading"
        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
      >
        <RefreshCw :class="['w-4 h-4', { 'animate-spin': loading }]" />
        Refresh
      </button>
    </div>

    <!-- Error Alert -->
    <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
      <div class="flex items-center gap-2 text-red-800">
        <AlertCircle class="w-5 h-5" />
        {{ error }}
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="-mb-px flex gap-8">
        <button
          @click="handleTabChange('overview')"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === 'overview'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          <div class="flex items-center gap-2">
            <BarChart3 class="w-4 h-4" />
            Overview
          </div>
        </button>
        <button
          @click="handleTabChange('at-risk')"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === 'at-risk'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          <div class="flex items-center gap-2">
            <Users class="w-4 h-4" />
            At-Risk Students
            <span v-if="atRiskStudents.length" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800">
              {{ atRiskStudents.length }}
            </span>
          </div>
        </button>
        <button
          @click="handleTabChange('weekly')"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === 'weekly'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          <div class="flex items-center gap-2">
            <Calendar class="w-4 h-4" />
            Weekly Prediction
          </div>
        </button>
        <button
          @click="handleTabChange('insights')"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === 'insights'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          <div class="flex items-center gap-2">
            <Activity class="w-4 h-4" />
            Insights & Recommendations
          </div>
        </button>
      </nav>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Overview Tab -->
    <div v-else-if="activeTab === 'overview' && insights">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- Attendance Rate -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500">Attendance Rate</span>
            <TrendingUp class="w-5 h-5 text-green-600" />
          </div>
          <div class="text-3xl font-bold text-gray-900">
            {{ formatPercent(insights.summary.overall_attendance_rate) }}
          </div>
          <div class="mt-2 text-sm text-gray-500">Last 30 days</div>
        </div>

        <!-- Trend -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500">Trend</span>
            <component 
              :is="insights.historical.trend.trend === 'declining' ? TrendingDown : TrendingUp" 
              :class="['w-5 h-5', insights.historical.trend.trend === 'declining' ? 'text-red-600' : 'text-green-600']"
            />
          </div>
          <div class="text-3xl font-bold text-gray-900 capitalize">
            {{ insights.historical.trend.trend }}
          </div>
          <div class="mt-2 text-sm" :class="insights.historical.trend.change >= 0 ? 'text-green-600' : 'text-red-600'">
            {{ insights.historical.trend.change >= 0 ? '+' : '' }}{{ insights.historical.trend.change.toFixed(1) }}% vs last week
          </div>
        </div>

        <!-- At Risk Students -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500">At-Risk Students</span>
            <AlertTriangle class="w-5 h-5 text-orange-600" />
          </div>
          <div class="text-3xl font-bold text-gray-900">
            {{ insights.at_risk.count }}
          </div>
          <div class="mt-2 text-sm text-gray-500">
            <span class="text-red-600 font-medium">{{ insights.at_risk.critical_count }} critical</span>
            <span class="mx-1">·</span>
            <span class="text-orange-600 font-medium">{{ insights.at_risk.high_count }} high</span>
          </div>
        </div>

        <!-- Expected Absences -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500">Expected Absences</span>
            <Calendar class="w-5 h-5 text-blue-600" />
          </div>
          <div class="text-3xl font-bold text-gray-900">
            {{ insights.predictions.expected_absences_next_week }}
          </div>
          <div class="mt-2 text-sm text-gray-500">Next week</div>
        </div>
      </div>

      <!-- High Risk Days -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">High Risk Days This Week</h3>
        <div class="flex flex-wrap gap-2">
          <span 
            v-for="day in insights.predictions.highest_risk_days" 
            :key="day"
            class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-medium"
          >
            {{ day }}
          </span>
          <span v-if="!insights.predictions.highest_risk_days.length" class="text-gray-500">
            No specific high-risk days identified
          </span>
        </div>
      </div>

      <!-- Top Recommendations -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Recommendations</h3>
        <div class="space-y-4">
          <div 
            v-for="(rec, index) in insights.recommendations.slice(0, 3)" 
            :key="index"
            :class="['p-4 rounded-lg border', priorityColors[rec.priority]]"
          >
            <div class="flex items-start gap-3">
              <component 
                :is="rec.priority === 'critical' ? AlertCircle : Info" 
                class="w-5 h-5 mt-0.5" 
              />
              <div>
                <p class="font-medium">{{ rec.message }}</p>
                <p class="text-sm mt-1 opacity-80">{{ rec.action }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- At-Risk Students Tab -->
    <div v-else-if="activeTab === 'at-risk'">
      <!-- Filter -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <label class="text-sm font-medium text-gray-700">Risk Threshold:</label>
          <select 
            v-model="threshold" 
            @change="loadAtRiskStudents"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option :value="20">Below 80% attendance</option>
            <option :value="30">Below 70% attendance</option>
            <option :value="40">Below 60% attendance</option>
          </select>
        </div>
        <div class="text-sm text-gray-500">
          Showing {{ atRiskStudents.length }} at-risk students
        </div>
      </div>

      <!-- Students Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trend</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consecutive</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Level</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="student in atRiskStudents" :key="student.student.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ student.student.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ student.student.class || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div 
                      class="bg-blue-600 h-2 rounded-full" 
                      :style="{ width: `${student.attendance_rate}%` }"
                    ></div>
                  </div>
                  <span class="text-sm text-gray-600">{{ student.attendance_rate }}%</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-1">
                  <component 
                    :is="student.trend === 'declining' ? TrendingDown : student.trend === 'improving' ? TrendingUp : Activity" 
                    :class="['w-4 h-4', student.trend === 'declining' ? 'text-red-500' : student.trend === 'improving' ? 'text-green-500' : 'text-gray-400']"
                  />
                  <span class="text-sm capitalize" :class="student.trend === 'declining' ? 'text-red-600' : student.trend === 'improving' ? 'text-green-600' : 'text-gray-500'">
                    {{ student.trend }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  v-if="student.consecutive_absences > 0"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="student.consecutive_absences >= 3 ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800'"
                >
                  {{ student.consecutive_absences }} days
                </span>
                <span v-else class="text-sm text-gray-400">None</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['px-2 py-1 text-xs font-medium rounded-full', riskLevelColors[student.risk_level]]">
                  {{ student.risk_level }}
                </span>
                <div class="mt-1 w-16 bg-gray-200 rounded-full h-1.5">
                  <div 
                    class="h-1.5 rounded-full"
                    :class="{
                      'bg-red-500': student.risk_level === 'CRITICAL',
                      'bg-orange-500': student.risk_level === 'HIGH',
                      'bg-yellow-500': student.risk_level === 'MEDIUM',
                      'bg-green-500': student.risk_level === 'LOW'
                    }"
                    :style="{ width: `${student.risk_score}%` }"
                  ></div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <button 
                  @click="loadStudentPrediction(student)"
                  class="text-blue-600 hover:text-blue-900 text-sm font-medium flex items-center justify-end gap-1 ml-auto"
                >
                  Details
                  <ChevronRight class="w-4 h-4" />
                </button>
              </td>
            </tr>
            <tr v-if="!atRiskStudents.length">
              <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                <CheckCircle2 class="w-12 h-12 mx-auto mb-3 text-green-400" />
                <p>No at-risk students found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Weekly Prediction Tab -->
    <div v-else-if="activeTab === 'weekly' && weeklyPrediction">
      <!-- Week Header -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              Week of {{ formatDate(weeklyPrediction.week.start_date) }} - {{ formatDate(weeklyPrediction.week.end_date) }}
            </h3>
            <p class="text-gray-500 mt-1">Predicted absences based on historical patterns</p>
          </div>
          <div class="text-right">
            <div class="text-4xl font-bold text-gray-900">{{ weeklyPrediction.predicted_absences }}</div>
            <div class="text-sm text-gray-500">Expected absences</div>
          </div>
        </div>
      </div>

      <!-- Daily Predictions -->
      <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
        <div 
          v-for="day in weeklyPrediction.daily_predictions" 
          :key="day.day"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-4"
        >
          <div class="text-center mb-4">
            <div class="text-sm font-medium text-gray-500">{{ day.day }}</div>
            <div class="text-lg font-semibold text-gray-900">{{ formatDate(day.date) }}</div>
          </div>
          <div class="text-center">
            <div 
              class="text-3xl font-bold mb-1"
              :class="day.expected_absences > 5 ? 'text-red-600' : day.expected_absences > 2 ? 'text-orange-600' : 'text-green-600'"
            >
              {{ day.expected_absences }}
            </div>
            <div class="text-xs text-gray-500">expected absences</div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="text-xs text-gray-500 text-center">
              Historical rate: {{ day.historical_absence_rate.toFixed(1) }}%
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Insights Tab -->
    <div v-else-if="activeTab === 'insights' && insights">
      <div class="space-y-6">
        <!-- Recommendations -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Actionable Recommendations</h3>
          <div class="space-y-4">
            <div 
              v-for="(rec, index) in insights.recommendations" 
              :key="index"
              :class="['p-4 rounded-lg border', priorityColors[rec.priority]]"
            >
              <div class="flex items-start gap-3">
                <component 
                  :is="rec.priority === 'critical' ? AlertCircle : rec.priority === 'high' ? AlertTriangle : Info" 
                  class="w-5 h-5 mt-0.5 flex-shrink-0" 
                />
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-medium uppercase px-2 py-0.5 rounded bg-white/50">
                      {{ rec.priority }}
                    </span>
                    <span class="text-xs text-gray-600 capitalize">{{ rec.category }}</span>
                  </div>
                  <p class="font-medium">{{ rec.message }}</p>
                  <p class="text-sm mt-2 opacity-80">{{ rec.action }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Historical Analysis -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Historical Analysis</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 bg-gray-50 rounded-lg">
              <div class="text-sm text-gray-500 mb-1">Last 7 Days</div>
              <div class="text-2xl font-bold text-gray-900">
                {{ insights.historical.trend.last_7_days_rate.toFixed(1) }}%
              </div>
              <div class="text-xs text-gray-500">Attendance rate</div>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
              <div class="text-sm text-gray-500 mb-1">Previous 7 Days</div>
              <div class="text-2xl font-bold text-gray-900">
                {{ insights.historical.trend.previous_7_days_rate.toFixed(1) }}%
              </div>
              <div class="text-xs text-gray-500">Attendance rate</div>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
              <div class="text-sm text-gray-500 mb-1">Change</div>
              <div 
                class="text-2xl font-bold"
                :class="insights.historical.trend.change >= 0 ? 'text-green-600' : 'text-red-600'"
              >
                {{ insights.historical.trend.change >= 0 ? '+' : '' }}{{ insights.historical.trend.change.toFixed(1) }}%
              </div>
              <div class="text-xs text-gray-500">Week over week</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Student Prediction Modal -->
    <div v-if="showStudentModal && studentPrediction" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold text-gray-900">{{ studentPrediction.student.name }}</h2>
              <p class="text-gray-500">{{ studentPrediction.student.class }}</p>
            </div>
            <button @click="showStudentModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        
        <div class="p-6 space-y-6">
          <!-- Risk Score -->
          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <div class="text-sm text-gray-500">Risk Score</div>
              <div class="text-3xl font-bold text-gray-900">{{ studentPrediction.risk_score }}/100</div>
            </div>
            <span :class="['px-3 py-1 text-lg font-medium rounded-full', riskLevelColors[studentPrediction.risk_level]]">
              {{ studentPrediction.risk_level }}
            </span>
          </div>

          <!-- Attendance Stats -->
          <div class="grid grid-cols-2 gap-4">
            <div class="p-4 border border-gray-200 rounded-lg">
              <div class="text-sm text-gray-500">Attendance Rate</div>
              <div class="text-2xl font-bold text-gray-900">{{ studentPrediction.attendance_rate }}%</div>
            </div>
            <div class="p-4 border border-gray-200 rounded-lg">
              <div class="text-sm text-gray-500">Trend</div>
              <div class="flex items-center gap-2">
                <component 
                  :is="studentPrediction.trend === 'declining' ? TrendingDown : studentPrediction.trend === 'improving' ? TrendingUp : Activity"
                  :class="['w-5 h-5', studentPrediction.trend === 'declining' ? 'text-red-500' : studentPrediction.trend === 'improving' ? 'text-green-500' : 'text-gray-400']"
                />
                <span class="text-xl font-bold text-gray-900 capitalize">{{ studentPrediction.trend }}</span>
              </div>
            </div>
          </div>

          <!-- Prediction -->
          <div class="p-4 border border-gray-200 rounded-lg">
            <div class="text-sm text-gray-500 mb-2">Prediction</div>
            <div class="flex items-center justify-between">
              <span class="text-lg font-medium capitalize">{{ studentPrediction.prediction.replace('_', ' ') }}</span>
              <span class="text-sm text-gray-500">{{ studentPrediction.confidence }}% confidence</span>
            </div>
          </div>

          <!-- Consecutive Absences -->
          <div v-if="studentPrediction.consecutive_absences > 0" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center gap-2 text-red-800">
              <AlertTriangle class="w-5 h-5" />
              <span class="font-medium">{{ studentPrediction.consecutive_absences }} consecutive absences</span>
            </div>
          </div>

          <!-- Risk Factors -->
          <div v-if="studentPrediction.factors.length">
            <div class="text-sm text-gray-500 mb-3">Contributing Factors</div>
            <div class="space-y-2">
              <div 
                v-for="(factor, index) in studentPrediction.factors" 
                :key="index"
                class="flex items-start gap-2 p-3 bg-gray-50 rounded-lg"
              >
                <Info class="w-4 h-4 text-gray-400 mt-0.5" />
                <div>
                  <div class="font-medium text-gray-900">{{ factor.factor }}</div>
                  <div class="text-sm text-gray-500">{{ factor.description }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.prediction-dashboard {
  @apply p-6;
}
</style>
