<script setup lang="ts">
import { Plus, MessageSquare } from 'lucide-vue-next';
import { cn } from '../../utils/cn';

const props = defineProps<{
  isOpen: boolean;
  selectedAttendance: any;
  followUpForm: any;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'update:followUpForm', form: any): void;
  (e: 'submit'): void;
  (e: 'sendAlert'): void;
}>();

const updateForm = (key: string, value: any) => {
  emit('update:followUpForm', { ...props.followUpForm, [key]: value });
};
</script>

<template>
  <div v-if="isOpen && selectedAttendance" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
      <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
        <div>
          <h3 class="text-xl font-bold text-slate-900">{{ selectedAttendance.name }}</h3>
          <p class="text-sm text-slate-500">{{ selectedAttendance.class }} • {{ selectedAttendance.date }}</p>
        </div>
        <button @click="emit('close')" class="p-2 hover:bg-slate-200 rounded-full transition-colors">
          <Plus :size="24" class="rotate-45 text-slate-400" />
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-6 space-y-8">
        <!-- Contact Info -->
        <div class="bg-[#135bec]/5 p-4 rounded-2xl border border-[#135bec]/10">
          <h4 class="text-xs font-bold text-[#135bec] uppercase tracking-widest mb-2">Contact Information</h4>
          <p class="text-sm text-slate-700 font-medium">{{ selectedAttendance.contact_info }}</p>
        </div>

        <!-- Follow-up Form -->
        <div class="space-y-6">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Absence Reason</label>
              <select 
                :value="followUpForm.reason"
                @change="(e) => updateForm('reason', (e.target as HTMLSelectElement).value)"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#135bec]/20"
              >
                <option value="">Select Reason</option>
                <option value="Flu/Sickness">Flu/Sickness</option>
                <option value="Family Emergency">Family Emergency</option>
                <option value="Personal">Personal</option>
                <option value="Unknown">Unknown</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Follow-up Status</label>
              <select 
                :value="followUpForm.status"
                @change="(e) => updateForm('status', (e.target as HTMLSelectElement).value)"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#135bec]/20"
              >
                <option value="Not Contacted">Not Contacted</option>
                <option value="Notified Parent">Notified Parent</option>
                <option value="In Progress">In Progress</option>
                <option value="Resolved">Resolved</option>
              </select>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <div class="flex-1">
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Attendance Type</label>
              <div class="flex gap-4">
                <button 
                  @click="updateForm('isExcused', true)"
                  :class="cn(
                    'flex-1 py-2.5 rounded-xl text-xs font-bold border transition-all',
                    followUpForm.isExcused ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-200 text-slate-500'
                  )"
                >
                  Excused
                </button>
                <button 
                  @click="updateForm('isExcused', false)"
                  :class="cn(
                    'flex-1 py-2.5 rounded-xl text-xs font-bold border transition-all',
                    !followUpForm.isExcused ? 'bg-rose-500 border-rose-500 text-white' : 'border-slate-200 text-slate-500'
                  )"
                >
                  Unexcused
                </button>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Add Comment</label>
            <textarea 
              :value="followUpForm.comment"
              @input="(e) => updateForm('comment', (e.target as HTMLTextAreaElement).value)"
              placeholder="Enter comment history..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#135bec]/20 h-24 resize-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Follow-up Note</label>
            <input 
              type="text"
              :value="followUpForm.note"
              @input="(e) => updateForm('note', (e.target as HTMLInputElement).value)"
              placeholder="Action taken..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#135bec]/20"
            />
          </div>

          <div class="flex items-center gap-3">
            <input 
              type="checkbox" 
              id="resolved"
              :checked="followUpForm.resolved"
              @change="(e) => updateForm('resolved', (e.target as HTMLInputElement).checked)"
              class="size-5 rounded border-slate-300 text-[#135bec] focus:ring-[#135bec]/20"
            />
            <label for="resolved" class="text-sm font-bold text-slate-700">Mark case as resolved</label>
          </div>
        </div>

        <!-- History Timeline -->
        <div class="space-y-4">
          <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Follow-up History</h4>
          <div class="space-y-4 relative before:absolute before:left-3 before:top-2 before:bottom-2 before:w-px before:bg-slate-100">
            <div v-for="(f, i) in selectedAttendance.followUps" :key="i" class="relative pl-8">
              <div class="absolute left-1.5 top-1.5 size-3 rounded-full bg-[#135bec] border-2 border-white"></div>
              <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                <div class="flex justify-between items-start mb-1">
                  <div class="flex flex-col">
                    <span class="text-xs font-bold text-slate-900">{{ f.updated_by }}</span>
                    <span v-if="f.status" class="text-[10px] text-[#135bec] font-bold uppercase tracking-wider">{{ f.status }}</span>
                  </div>
                  <span class="text-[10px] text-slate-400 font-medium">{{ new Date(f.timestamp).toLocaleString() }}</span>
                </div>
                <p class="text-xs text-slate-600">{{ f.comment }}</p>
                <p v-if="f.note" class="text-[10px] text-[#135bec] font-bold mt-1">Action: {{ f.note }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex gap-4">
        <button 
          @click="emit('sendAlert')"
          class="px-6 py-3 rounded-2xl text-sm font-bold bg-blue-500 text-white hover:bg-blue-600 shadow-lg shadow-blue-200 transition-all flex items-center gap-2"
        >
          <MessageSquare :size="18" />
          Send Alert
        </button>
        <div class="flex-1 flex gap-4">
          <button 
            @click="emit('close')"
            class="flex-1 py-3 rounded-2xl text-sm font-bold text-slate-600 hover:bg-slate-200 transition-all"
          >
            Cancel
          </button>
          <button 
            @click="emit('submit')"
            class="flex-1 py-3 rounded-2xl text-sm font-bold bg-[#135bec] text-white hover:bg-[#135bec]/90 shadow-lg shadow-[#135bec]/20 transition-all"
          >
            Save Follow-up
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
