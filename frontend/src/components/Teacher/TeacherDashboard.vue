<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import {
  Calendar,
  Users,
  UserX,
  BookOpen,
  ChevronRight,
  AlertCircle,
  Clock,
  MapPin,
  GraduationCap,
} from "lucide-vue-next";
import { ViewType } from "./Sidebar.vue";
import { teacherService } from "../../services/teacherService";

interface User {
  name: string;
  role: "teacher" | "admin";
  department?: string;
  photo?: string;
}

interface ScheduleSession {
  session_number: number;
  session_name: string;
  subject: string;
  room: string;
  class: string;
  academic_year: string;
  start_time: string;
  end_time: string;
}

const props = defineProps<{ user: User }>();
const emit = defineEmits<{ (e: "navigate", view: ViewType): void }>();

const loading = ref(false);
const errorMessage = ref("");
const dashboard = ref<any>({
  today_classes: [],
  active: null,
  next_today: null,
  checked_in_count: 0,
  absent_count: 0,
});
const justifications = ref<any[]>([]);

// New schedule data from external timetable API
const todaySchedule = ref<{
  date: string;
  sessions: ScheduleSession[];
  total_sessions: number;
}>({
  date: "",
  sessions: [],
  total_sessions: 0,
});

const loadDashboard = async () => {
  loading.value = true;
  errorMessage.value = "";
  try {
    const [dashboardData, justificationData] = await Promise.all([
      teacherService.getDashboard(),
      teacherService.getJustifications(),
    ]);
    dashboard.value = dashboardData || dashboard.value;
    justifications.value = (
      Array.isArray(justificationData) ? justificationData : []
    ).slice(0, 2);

    // Load today's schedule from external timetable API
    await loadTodaySchedule();
  } catch (error: any) {
    errorMessage.value = error.message || "Failed to load teacher dashboard.";
  } finally {
    loading.value = false;
  }
};

// Load today's schedule from external timetable API
const loadTodaySchedule = async () => {
  try {
    const scheduleData = await teacherService.getTodaySchedule();
    if (scheduleData.success) {
      todaySchedule.value = {
        date: scheduleData.date,
        sessions: scheduleData.sessions || [],
        total_sessions: scheduleData.total_sessions || 0,
      };
    }
  } catch (error: any) {
    console.error("Failed to load today's schedule:", error);
    // Don't show error to user, just log it
  }
};

const todayClasses = computed(() =>
  Array.isArray(dashboard.value.today_classes)
    ? dashboard.value.today_classes
    : [],
);

// Get session by number (1-4)
const getSessionByNumber = (num: number): ScheduleSession | undefined => {
  return todaySchedule.value.sessions.find((s) => s.session_number === num);
};

// Format date for display
const formatDate = (dateStr: string) => {
  if (!dateStr) return "";
  const date = new Date(dateStr);
  return date.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
};

onMounted(async () => {
  await loadDashboard();
  await loadTodaySchedule();
});
</script>

<template>
  <div class="space-y-8">
    <div
      class="flex flex-col md:flex-row md:items-center justify-between gap-4"
    >
      <div>
        <h2 class="text-3xl font-black tracking-tight text-slate-900">
          Welcome, {{ props.user.name }}
        </h2>
        <p class="text-slate-500 font-medium">
          Live data from teacher backend endpoints
        </p>
      </div>
      <button
        class="px-6 py-3 bg-primary text-white rounded-xl font-bold text-sm shadow-xl shadow-primary/20 hover:bg-primary/90 transition-all disabled:opacity-60"
        :disabled="loading"
        @click="loadDashboard"
      >
        {{ loading ? "Refreshing..." : "Refresh Dashboard" }}
      </button>
    </div>

    <p
      v-if="errorMessage"
      class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm"
    >
      {{ errorMessage }}
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-4">
          <div class="p-3 rounded-xl bg-blue-50">
            <Calendar class="size-6 text-blue-600" />
          </div>
          <div class="text-right">
            <p
              class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
            >
              Sessions Today
            </p>
            <h3 class="text-2xl font-black text-slate-900">
              {{ todaySchedule.total_sessions }}
            </h3>
          </div>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-4">
          <div class="p-3 rounded-xl bg-indigo-50">
            <BookOpen class="size-6 text-indigo-600" />
          </div>
          <div class="text-right">
            <p
              class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
            >
              Current Session
            </p>
            <h3 class="text-2xl font-black text-slate-900">
              {{ dashboard.active?.subject || "None" }}
            </h3>
          </div>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-4">
          <div class="p-3 rounded-xl bg-green-50">
            <Users class="size-6 text-green-600" />
          </div>
          <div class="text-right">
            <p
              class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
            >
              Checked-in Count
            </p>
            <h3 class="text-2xl font-black text-slate-900">
              {{ dashboard.checked_in_count || 0 }}
            </h3>
          </div>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-4">
          <div class="p-3 rounded-xl bg-red-50">
            <UserX class="size-6 text-red-600" />
          </div>
          <div class="text-right">
            <p
              class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
            >
              Absent Count
            </p>
            <h3 class="text-2xl font-black text-slate-900">
              {{ dashboard.absent_count || 0 }}
            </h3>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-6">
        <!-- Today's Schedule from External Timetable API -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-900">Today's Schedule</h3>
            <span class="text-xs font-medium text-slate-500">
              {{ todaySchedule.date ? formatDate(todaySchedule.date) : "" }}
            </span>
          </div>

          <!-- Session Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- First Session -->
            <div
              class="bg-white p-5 rounded-2xl border-2 border-slate-200 hover:border-primary/30 transition-colors"
            >
              <div class="flex items-center gap-3 mb-3">
                <div class="p-2 rounded-lg bg-primary/10">
                  <span class="text-xs font-bold text-primary">1st</span>
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase"
                  >First Session</span
                >
              </div>
              <div v-if="getSessionByNumber(1)" class="space-y-2">
                <h4 class="font-bold text-slate-900">
                  {{ getSessionByNumber(1)?.subject }}
                </h4>
                <div class="flex items-center gap-2 text-sm text-slate-600">
                  <MapPin class="size-4 text-slate-400" />
                  <span>Room: {{ getSessionByNumber(1)?.room || "N/A" }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-slate-600">
                  <GraduationCap class="size-4 text-slate-400" />
                  <span
                    >Class:
                    {{
                      getSessionByNumber(1)?.academic_year ||
                      getSessionByNumber(1)?.class ||
                      "N/A"
                    }}</span
                  >
                </div>
                <div
                  class="flex items-center gap-2 text-xs text-slate-500 mt-2"
                >
                  <Clock class="size-3" />
                  <span
                    >{{ getSessionByNumber(1)?.start_time }} -
                    {{ getSessionByNumber(1)?.end_time }}</span
                  >
                </div>
              </div>
              <div v-else class="text-sm text-slate-400 italic">
                No class scheduled
              </div>
            </div>

            <!-- Second Session -->
            <div
              class="bg-white p-5 rounded-2xl border-2 border-slate-200 hover:border-primary/30 transition-colors"
            >
              <div class="flex items-center gap-3 mb-3">
                <div class="p-2 rounded-lg bg-primary/10">
                  <span class="text-xs font-bold text-primary">2nd</span>
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase"
                  >Second Session</span
                >
              </div>
              <div v-if="getSessionByNumber(2)" class="space-y-2">
                <h4 class="font-bold text-slate-900">
                  {{ getSessionByNumber(2)?.subject }}
                </h4>
                <div class="flex items-center gap-2 text-sm text-slate-600">
                  <MapPin class="size-4 text-slate-400" />
                  <span>Room: {{ getSessionByNumber(2)?.room || "N/A" }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-slate-600">
                  <GraduationCap class="size-4 text-slate-400" />
                  <span
                    >Class:
                    {{
                      getSessionByNumber(2)?.academic_year ||
                      getSessionByNumber(2)?.class ||
                      "N/A"
                    }}</span
                  >
                </div>
                <div
                  class="flex items-center gap-2 text-xs text-slate-500 mt-2"
                >
                  <Clock class="size-3" />
                  <span
                    >{{ getSessionByNumber(2)?.start_time }} -
                    {{ getSessionByNumber(2)?.end_time }}</span
                  >
                </div>
              </div>
              <div v-else class="text-sm text-slate-400 italic">
                No class scheduled
              </div>
            </div>

            <!-- Third Session -->
            <div
              class="bg-white p-5 rounded-2xl border-2 border-slate-200 hover:border-primary/30 transition-colors"
            >
              <div class="flex items-center gap-3 mb-3">
                <div class="p-2 rounded-lg bg-primary/10">
                  <span class="text-xs font-bold text-primary">3rd</span>
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase"
                  >Third Session</span
                >
              </div>
              <div v-if="getSessionByNumber(3)" class="space-y-2">
                <h4 class="font-bold text-slate-900">
                  {{ getSessionByNumber(3)?.subject }}
                </h4>
                <div class="flex items-center gap-2 text-sm text-slate-600">
                  <MapPin class="size-4 text-slate-400" />
                  <span>Room: {{ getSessionByNumber(3)?.room || "N/A" }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-slate-600">
                  <GraduationCap class="size-4 text-slate-400" />
                  <span
                    >Class:
                    {{
                      getSessionByNumber(3)?.academic_year ||
                      getSessionByNumber(3)?.class ||
                      "N/A"
                    }}</span
                  >
                </div>
                <div
                  class="flex items-center gap-2 text-xs text-slate-500 mt-2"
                >
                  <Clock class="size-3" />
                  <span
                    >{{ getSessionByNumber(3)?.start_time }} -
                    {{ getSessionByNumber(3)?.end_time }}</span
                  >
                </div>
              </div>
              <div v-else class="text-sm text-slate-400 italic">
                No class scheduled
              </div>
            </div>

            <!-- Fourth Session -->
            <div
              class="bg-white p-5 rounded-2xl border-2 border-slate-200 hover:border-primary/30 transition-colors"
            >
              <div class="flex items-center gap-3 mb-3">
                <div class="p-2 rounded-lg bg-primary/10">
                  <span class="text-xs font-bold text-primary">4th</span>
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase"
                  >Fourth Session</span
                >
              </div>
              <div v-if="getSessionByNumber(4)" class="space-y-2">
                <h4 class="font-bold text-slate-900">
                  {{ getSessionByNumber(4)?.subject }}
                </h4>
                <div class="flex items-center gap-2 text-sm text-slate-600">
                  <MapPin class="size-4 text-slate-400" />
                  <span>Room: {{ getSessionByNumber(4)?.room || "N/A" }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-slate-600">
                  <GraduationCap class="size-4 text-slate-400" />
                  <span
                    >Class:
                    {{
                      getSessionByNumber(4)?.academic_year ||
                      getSessionByNumber(4)?.class ||
                      "N/A"
                    }}</span
                  >
                </div>
                <div
                  class="flex items-center gap-2 text-xs text-slate-500 mt-2"
                >
                  <Clock class="size-3" />
                  <span
                    >{{ getSessionByNumber(4)?.start_time }} -
                    {{ getSessionByNumber(4)?.end_time }}</span
                  >
                </div>
              </div>
              <div v-else class="text-sm text-slate-400 italic">
                No class scheduled
              </div>
            </div>
          </div>

          <!-- No Schedule Message -->
          <div
            v-if="!loading && todaySchedule.total_sessions === 0"
            class="bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-8 text-center"
          >
            <Calendar class="size-10 text-slate-300 mx-auto mb-3" />
            <p
              class="text-sm font-bold text-slate-400 uppercase tracking-widest"
            >
              No classes found for today from timetable
            </p>
          </div>

          <button
            @click="emit('navigate', 'schedule')"
            class="text-xs font-bold text-primary hover:underline"
          >
            View Full Calendar
          </button>
        </div>

        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-900">
              Absence Justifications
            </h3>
            <button
              @click="emit('navigate', 'messages')"
              class="text-xs font-bold text-primary hover:underline"
            >
              View All Reports
            </button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="j in justifications"
              :key="j.id"
              class="bg-white p-5 rounded-2xl border border-slate-200"
            >
              <div class="flex items-center gap-3 mb-4">
                <img
                  v-if="j.studentPhoto"
                  :src="j.studentPhoto"
                  class="size-10 rounded-xl object-cover"
                  alt=""
                  referrerPolicy="no-referrer"
                />
                <div v-else class="size-10 rounded-xl bg-slate-100" />
                <div class="flex-1">
                  <p class="text-sm font-bold text-slate-900">
                    {{ j.studentName }}
                  </p>
                  <p
                    class="text-[10px] font-bold text-primary uppercase tracking-widest"
                  >
                    {{ j.classCode }}
                  </p>
                </div>
                <span
                  :class="[
                    'px-2 py-1 text-[10px] font-bold rounded-full uppercase',
                    j.status === 'late'
                      ? 'bg-amber-100 text-amber-700'
                      : 'bg-red-100 text-red-700',
                  ]"
                >
                  {{ j.status }}
                </span>
              </div>
              <p class="text-xs text-slate-600 italic line-clamp-2">
                "{{ j.educationComment }}"
              </p>
            </div>
            <div
              v-if="!loading && justifications.length === 0"
              class="md:col-span-2 bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-8 text-center"
            >
              <p
                class="text-xs font-bold text-slate-400 uppercase tracking-widest"
              >
                No absence reports available
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
          <h3 class="text-lg font-black text-slate-900 mb-3">Session Status</h3>
          <p class="text-sm text-slate-600">
            {{
              dashboard.active
                ? `Active: ${dashboard.active.subject}`
                : "No active session at the moment."
            }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
