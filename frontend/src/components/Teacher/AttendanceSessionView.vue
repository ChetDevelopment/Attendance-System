<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { attendanceService } from "../../services/attendanceService";
import { teacherService } from "../../services/teacherService";

// Props
const props = defineProps<{
  academicYearId?: number | null;
  academicYearOptions?: { id: number; name: string }[];
}>();

const emit = defineEmits<{
  (e: "update:academicYearId", value: number | null): void;
}>();

// Method to handle academic year change
const onAcademicYearChange = (event: Event) => {
  const target = event.target as HTMLSelectElement;
  // Handle "All Years" option (value is null or empty string)
  const value =
    target.value && target.value !== "" ? Number(target.value) : null;
  emit("update:academicYearId", value);
};

type StatusType = "Present" | "Absent" | "Late" | "Excused";

const loading = ref(false);
const saving = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const searchQuery = ref("");

const sessions = ref<any[]>([]);
const classes = ref<any[]>([]);
const students = ref<any[]>([]);
const selectedSessionId = ref<string>("");
const classId = ref<string>("");
const statuses = reactive<Record<number, StatusType>>({});

const loadData = async () => {
  loading.value = true;
  errorMessage.value = "";
  try {
    // Pass academic year ID to filter classes
    const params = props.academicYearId
      ? { academic_year_id: props.academicYearId }
      : {};
    const scheduleData = await teacherService.getSchedule(params);

    sessions.value = Array.isArray(scheduleData.sessions)
      ? scheduleData.sessions
      : [];
    classes.value = Array.isArray(scheduleData.classes)
      ? scheduleData.classes
      : [];

    // Set default class to first available class for this teacher
    const classIdValue =
      scheduleData.class_id ?? classId.value ?? classes.value[0]?.id ?? null;
    classId.value = String(classIdValue ?? "");

    if (classIdValue) {
      const studentsData = await teacherService.getStudents(classIdValue);
      students.value = Array.isArray(studentsData) ? studentsData : [];

      students.value.forEach((student) => {
        if (!statuses[student.id]) statuses[student.id] = "Present";
      });
    }

    if (!selectedSessionId.value && sessions.value.length > 0) {
      selectedSessionId.value = String(sessions.value[0].id);
    }
  } catch (error: any) {
    errorMessage.value =
      error.message || "Failed to load attendance session data.";
  } finally {
    loading.value = false;
  }
};

// Watch for class changes and reload students
watch(classId, async (newClassId) => {
  if (newClassId) {
    loading.value = true;
    try {
      const studentsData = await teacherService.getStudents(newClassId);
      students.value = Array.isArray(studentsData) ? studentsData : [];

      // Reset statuses when class changes
      Object.keys(statuses).forEach((key) => delete statuses[key]);
      students.value.forEach((student) => {
        if (!statuses[student.id]) statuses[student.id] = "Present";
      });
    } catch (error: any) {
      errorMessage.value =
        error.message || "Failed to load students for selected class.";
    } finally {
      loading.value = false;
    }
  }
});

// Watch for academic year changes and reload data
watch(
  () => props.academicYearId,
  () => {
    loadData();
  },
);

const filteredStudents = computed(() =>
  students.value.filter((student) => {
    const q = searchQuery.value.toLowerCase();
    return (
      String(student.name || "")
        .toLowerCase()
        .includes(q) ||
      String(student.student_code || "")
        .toLowerCase()
        .includes(q)
    );
  }),
);

const saveAttendance = async () => {
  if (saving.value) return;
  if (!selectedSessionId.value) {
    errorMessage.value = "Please select a session before saving attendance.";
    return;
  }
  if (!classId.value) {
    errorMessage.value = "No class selected. Please refresh and try again.";
    return;
  }

  saving.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    for (const student of filteredStudents.value) {
      await attendanceService.markAttendance({
        class_id: Number(classId.value),
        student_id: student.id,
        session_id: Number(selectedSessionId.value),
        status: statuses[student.id] || "Present",
      });
    }
    successMessage.value = "Attendance saved successfully.";
  } catch (error: any) {
    errorMessage.value = error.message || "Failed to save attendance.";
  } finally {
    saving.value = false;
  }
};

onMounted(loadData);
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold">Attendance Session</h2>
      <button
        class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-bold"
        :disabled="loading"
        @click="loadData"
      >
        Refresh
      </button>
    </div>

    <p
      v-if="errorMessage"
      class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm"
    >
      {{ errorMessage }}
    </p>
    <p
      v-if="successMessage"
      class="p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm"
    >
      {{ successMessage }}
    </p>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="md:col-span-1">
        <label class="text-xs font-bold text-slate-500 uppercase"
          >Academic Year</label
        >
        <select
          :value="props.academicYearId"
          @change="onAcademicYearChange"
          class="mt-1 w-full px-3 py-2 border rounded-lg bg-white"
        >
          <option :value="null">All Years</option>
          <option
            v-for="year in academicYearOptions"
            :key="year.id"
            :value="year.id"
          >
            {{ year.name }}
          </option>
        </select>
      </div>
      <div class="md:col-span-1">
        <label class="text-xs font-bold text-slate-500 uppercase">Class</label>
        <select
          v-model="classId"
          class="mt-1 w-full px-3 py-2 border rounded-lg bg-white"
        >
          <option value="">Select class</option>
          <option v-for="cls in classes" :key="cls.id" :value="String(cls.id)">
            {{ cls.name }} ({{ cls.code }})
          </option>
        </select>
      </div>
      <div class="md:col-span-1">
        <label class="text-xs font-bold text-slate-500 uppercase"
          >Session</label
        >
        <select
          v-model="selectedSessionId"
          class="mt-1 w-full px-3 py-2 border rounded-lg bg-white"
        >
          <option value="">Select session</option>
          <option
            v-for="session in sessions"
            :key="session.id"
            :value="String(session.id)"
          >
            {{ session.name }} ({{ session.start_time }} -
            {{ session.end_time }})
          </option>
        </select>
      </div>
      <div class="md:col-span-1">
        <label class="text-xs font-bold text-slate-500 uppercase"
          >Search Student</label
        >
        <input
          v-model="searchQuery"
          class="mt-1 w-full px-3 py-2 border rounded-lg bg-white"
          placeholder="Search by name or code"
        />
      </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-xs text-slate-500 uppercase w-16">NO</th>
            <th class="px-4 py-3 text-xs text-slate-500 uppercase w-20">
              PHOTOS
            </th>
            <th class="px-4 py-3 text-xs text-slate-500 uppercase">Student</th>
            <th class="px-4 py-3 text-xs text-slate-500 uppercase">Code</th>
            <th class="px-4 py-3 text-xs text-slate-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td
              colspan="5"
              class="px-4 py-8 text-center text-sm text-slate-500"
            >
              Loading students...
            </td>
          </tr>
          <tr
            v-for="(student, index) in filteredStudents"
            :key="student.id"
            class="border-t"
          >
            <td class="px-4 py-3 text-sm text-slate-600">{{ index + 1 }}</td>
            <td class="px-4 py-3">
              <img
                v-if="student.photo"
                :src="student.photo"
                alt="Photo"
                class="w-10 h-10 rounded-full object-cover border border-slate-200"
              />
              <div
                v-else
                class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center"
              >
                <span class="text-xs text-slate-400">N/A</span>
              </div>
            </td>
            <td class="px-4 py-3 text-sm font-semibold">{{ student.name }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">
              {{ student.student_code }}
            </td>
            <td class="px-4 py-3">
              <select
                v-model="statuses[student.id]"
                class="px-3 py-1.5 border rounded-lg text-sm bg-white"
              >
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
                <option value="Late">Late</option>
                <option value="Excused">Excused</option>
              </select>
            </td>
          </tr>
          <tr v-if="!loading && filteredStudents.length === 0">
            <td
              colspan="5"
              class="px-4 py-8 text-center text-sm text-slate-500"
            >
              No students found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex justify-end">
      <button
        @click="saveAttendance"
        :disabled="saving || loading || !selectedSessionId"
        class="px-6 py-2.5 rounded-lg bg-primary text-white font-bold text-sm disabled:opacity-60"
      >
        {{ saving ? "Saving..." : "Save Attendance" }}
      </button>
    </div>
  </div>
</template>
