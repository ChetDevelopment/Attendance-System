<template>
  <div class="teacher-collab">
    <!-- Quick Overview -->
    <div class="collab-overview">
      <div class="overview-card">
        <span class="material-icons">people</span>
        <div class="overview-data">
          <span class="overview-num">{{ stats.teamMembers || 0 }}</span>
          <span class="overview-text">Team Members</span>
        </div>
      </div>
      <div class="overview-card">
        <span class="material-icons">pending</span>
        <div class="overview-data">
          <span class="overview-num">{{ stats.pendingRequests || 0 }}</span>
          <span class="overview-text">My Requests</span>
        </div>
      </div>
      <div class="overview-card">
        <span class="material-icons">history</span>
        <div class="overview-data">
          <span class="overview-num">{{ stats.recentActivities || 0 }}</span>
          <span class="overview-text">Recent</span>
        </div>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-nav">
      <button 
        v-for="tab in tabItems" 
        :key="tab.id"
        :class="['nav-tab', { active: currentTab === tab.id }]"
        @click="currentTab = tab.id"
      >
        <span class="material-icons">{{ tab.icon }}</span>
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab Content -->
    <div class="tab-body">
      <!-- Activity Feed -->
      <div v-if="currentTab === 'feeds'" class="feed-view">
        <div v-if="fetching" class="status-msg">
          <span class="material-icons spin">sync</span> Loading...
        </div>
        <div v-else-if="activities.length === 0" class="status-msg empty">
          <span class="material-icons">inbox</span> No activities yet
        </div>
        <div v-else class="feed-list">
          <div 
            v-for="item in activities" 
            :key="item.id"
            class="feed-entry"
          >
            <div :class="['entry-icon', item.role]">
              <span class="material-icons">{{ getIcon(item.type) }}</span>
            </div>
            <div class="entry-content">
              <div class="entry-header">
                <span class="entry-title">{{ item.title }}</span>
                <span class="entry-time">{{ ago(item.created_at) }}</span>
              </div>
              <p class="entry-desc">{{ item.description }}</p>
              <div class="entry-tags">
                <span :class="['tag', item.role]">{{ item.role_label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Team Members -->
      <div v-if="currentTab === 'members'" class="member-view">
        <div v-if="fetching" class="status-msg">
          <span class="material-icons spin">sync</span> Loading...
        </div>
        <div v-else-if="teamMembers.length === 0" class="status-msg empty">
          <span class="material-icons">group_off</span> No team found
        </div>
        <div v-else class="members-grid">
          <div 
            v-for="m in teamMembers" 
            :key="m.id"
            class="member-card"
          >
            <div class="member-photo">
              <img v-if="m.avatar" :src="m.avatar" :alt="m.name">
              <span v-else class="material-icons">person</span>
            </div>
            <div class="member-details">
              <div class="member-name">{{ m.name }}</div>
              <div class="member-pos">{{ m.role_label }}</div>
              <div :class="['member-state', { online: m.is_online }]">
                {{ m.is_online ? 'Online' : 'Offline' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Requests -->
      <div v-if="currentTab === 'myrequests'" class="request-view">
        <div class="request-bar">
          <button class="btn-new" @click="showForm = true">
            <span class="material-icons">add</span>
            New Request
          </button>
        </div>

        <div v-if="fetching" class="status-msg">
          <span class="material-icons spin">sync</span> Loading...
        </div>
        <div v-else-if="requests.length === 0" class="status-msg empty">
          <span class="material-icons">check_circle</span> No pending requests
        </div>
        <div v-else class="request-stack">
          <div 
            v-for="r in requests" 
            :key="r.id"
            class="request-box"
          >
            <div class="box-header">
              <span class="box-title">{{ r.title }}</span>
              <span :class="['box-prio', r.priority]">{{ r.priority }}</span>
            </div>
            <p class="box-text">{{ r.description }}</p>
            <div class="box-info">
              <span>From: {{ r.from_name }}</span>
              <span>To: {{ r.to_role }}</span>
            </div>
            <div class="box-btns">
              <button 
                v-if="r.status === 'pending'"
                class="btn-action approve"
                @click="respond(r.id, 'approved')"
              >
                Approve
              </button>
              <button 
                v-if="r.status === 'pending'"
                class="btn-action reject"
                @click="respond(r.id, 'rejected')"
              >
                Reject
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New Request Form Modal -->
    <div v-if="showForm" class="modal-cover" @click.self="showForm = false">
      <div class="modal-card">
        <div class="modal-head">
          <h3>Send Request</h3>
          <button class="btn-close" @click="showForm = false">
            <span class="material-icons">close</span>
          </button>
        </div>
        <form @submit.prevent="sendRequest">
          <div class="field">
            <label>Type</label>
            <select v-model="form.type" required>
              <option value="">Choose...</option>
              <option value="support">Support</option>
              <option value="coordination">Coordination</option>
              <option value="information">Info Request</option>
            </select>
          </div>
          <div class="field">
            <label>Title</label>
            <input v-model="form.title" type="text" required placeholder="Title">
          </div>
          <div class="field">
            <label>Description</label>
            <textarea v-model="form.description" rows="4" required placeholder="Details..."></textarea>
          </div>
          <div class="field">
            <label>Priority</label>
            <select v-model="form.priority" required>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="modal-btns">
            <button type="button" class="btn-cancel" @click="showForm = false">Cancel</button>
            <button type="submit" class="btn-submit" :disabled="sending">
              {{ sending ? 'Sending...' : 'Send' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { collaborationService } from '@/services/collaborationService'

const fetching = ref(false)
const sending = ref(false)
const currentTab = ref('feeds')
const showForm = ref(false)

const tabItems = [
  { id: 'feeds', label: 'Activity', icon: 'dynamic_feed' },
  { id: 'members', label: 'Team', icon: 'group' },
  { id: 'myrequests', label: 'Requests', icon: 'send' }
]

const stats = ref({})
const activities = ref([])
const teamMembers = ref([])
const requests = ref([])

const form = ref({
  type: '',
  title: '',
  description: '',
  priority: 'medium'
})

const loadData = async () => {
  fetching.value = true
  try {
    const [s, a, t, r] = await Promise.all([
      collaborationService.getQuickStats(),
      collaborationService.getActivityFeed({ limit: 10 }),
      collaborationService.getTeamMembers(),
      collaborationService.getPendingRequests()
    ])
    stats.value = s
    activities.value = a
    teamMembers.value = t
    requests.value = r
  } catch (e) {
    console.error('Error:', e)
  } finally {
    fetching.value = false
  }
}

const sendRequest = async () => {
  sending.value = true
  try {
    await collaborationService.createRequest(form.value)
    showForm.value = false
    form.value = { type: '', title: '', description: '', priority: 'medium' }
    await loadData()
  } catch (e) {
    alert(e.message || 'Failed to send')
  } finally {
    sending.value = false
  }
}

const respond = async (id, status) => {
  if (!confirm(`Are you sure you want to ${status}?`)) return
  try {
    await collaborationService.resolveRequest(id, status)
    await loadData()
  } catch (e) {
    alert(e.message || 'Action failed')
  }
}

const getIcon = (type) => {
  const icons = {
    attendance: 'event_available',
    absence: 'event_busy',
    followup: 'notifications_active',
    report: 'description',
    default: 'info'
  }
  return icons[type] || icons.default
}

const ago = (dateStr) => {
  if (!dateStr) return ''
  const diff = Date.now() - new Date(dateStr)
  const mins = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  if (mins < 1) return 'Just now'
  if (mins < 60) return `${mins}m`
  if (hours < 24) return `${hours}h`
  if (days < 7) return `${days}d`
  return new Date(dateStr).toLocaleDateString()
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.teacher-collab {
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 1px 6px rgba(0,0,0,0.06);
}

.collab-overview {
  display: flex;
  gap: 0.75rem;
  padding: 1rem;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.overview-card {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: rgba(255,255,255,0.25);
  border-radius: 8px;
}

.overview-card .material-icons {
  color: white;
  font-size: 1.375rem;
}

.overview-data {
  display: flex;
  flex-direction: column;
}

.overview-num {
  font-size: 1.125rem;
  font-weight: 700;
  color: white;
}

.overview-text {
  font-size: 0.6875rem;
  color: rgba(255,255,255,0.85);
}

.tab-nav {
  display: flex;
  padding: 0 1rem;
  border-bottom: 1px solid #f3f4f6;
  background: #fafafa;
}

.nav-tab {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.875rem 1rem;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
}

.nav-tab.active {
  color: #f59e0b;
  border-bottom-color: #f59e0b;
}

.nav-tab .material-icons {
  font-size: 1rem;
}

.tab-body {
  padding: 1rem;
  min-height: 320px;
}

.status-msg {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2.5rem;
  color: #a1a1aa;
}

.status-msg .material-icons {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.feed-list {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

.feed-entry {
  display: flex;
  gap: 0.875rem;
  padding: 0.875rem;
  background: #fefce8;
  border-radius: 8px;
  border-left: 3px solid #f59e0b;
}

.entry-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: white;
  flex-shrink: 0;
}

.entry-icon.admin { background: #3b82f6; }
.entry-icon.education { background: #10b981; }
.entry-icon.teacher { background: #f59e0b; }

.entry-content {
  flex: 1;
}

.entry-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}

.entry-title {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9375rem;
}

.entry-time {
  font-size: 0.6875rem;
  color: #a1a1aa;
}

.entry-desc {
  font-size: 0.8125rem;
  color: #6b7280;
  margin: 0.25rem 0;
}

.entry-tags .tag {
  font-size: 0.6875rem;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  text-transform: capitalize;
}

.tag.admin { background: #dbeafe; color: #1d4ed8; }
.tag.education { background: #d1fae5; color: #047857; }
.tag.teacher { background: #fef3c7; color: #b45309; }

.members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 0.75rem;
}

.member-card {
  padding: 1rem;
  background: #fafafa;
  border-radius: 8px;
  text-align: center;
  border: 1px solid #f3f4f6;
}

.member-photo {
  width: 48px;
  height: 48px;
  margin: 0 auto 0.625rem;
  border-radius: 50%;
  background: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.member-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.875rem;
}

.member-pos {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.125rem;
}

.member-state {
  font-size: 0.6875rem;
  color: #a1a1aa;
  margin-top: 0.25rem;
}

.member-state.online {
  color: #10b981;
}

.request-bar {
  margin-bottom: 0.75rem;
}

.btn-new {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 1rem;
  background: #f59e0b;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
}

.btn-new:hover {
  background: #d97706;
}

.btn-new .material-icons {
  font-size: 1rem;
}

.request-stack {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.request-box {
  padding: 0.875rem;
  border: 1px solid #f3f4f6;
  border-radius: 8px;
  background: #fafafa;
}

.box-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.375rem;
}

.box-title {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9375rem;
}

.box-prio {
  font-size: 0.6875rem;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  text-transform: capitalize;
}

.box-prio.low { background: #d1fae5; color: #047857; }
.box-prio.medium { background: #fef3c7; color: #b45309; }
.box-prio.high { background: #fee2e2; color: #dc2626; }
.box-prio.urgent { background: #fee2e2; color: #991b1b; }

.box-text {
  font-size: 0.8125rem;
  color: #6b7280;
  margin: 0.375rem 0;
}

.box-info {
  display: flex;
  gap: 1rem;
  font-size: 0.75rem;
  color: #a1a1aa;
  margin-bottom: 0.5rem;
}

.box-btns {
  display: flex;
  gap: 0.375rem;
}

.btn-action {
  padding: 0.3125rem 0.625rem;
  font-size: 0.75rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-action.approve {
  background: #10b981;
  color: white;
}

.btn-action.approve:hover {
  background: #059669;
}

.btn-action.reject {
  background: #ef4444;
  color: white;
}

.btn-action.reject:hover {
  background: #dc2626;
}

.modal-cover {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-card {
  background: white;
  border-radius: 10px;
  width: 100%;
  max-width: 420px;
}

.modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.modal-head h3 {
  margin: 0;
  font-size: 1rem;
  color: #1f2937;
}

.btn-close {
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
}

.modal-card form {
  padding: 1rem;
}

.field {
  margin-bottom: 0.875rem;
}

.field label {
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.8125rem;
}

.field input,
.field select,
.field textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
  outline: none;
  border-color: #f59e0b;
}

.modal-btns {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
}

.btn-cancel {
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
}

.btn-cancel:hover {
  background: #e5e7eb;
}

.btn-submit {
  padding: 0.5rem 1rem;
  background: #f59e0b;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
}

.btn-submit:hover {
  background: #d97706;
}

.btn-submit:disabled {
  background: #fcd34d;
  cursor: not-allowed;
}
</style>