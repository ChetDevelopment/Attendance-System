<template>
  <div class="collaboration-panel education-panel">
    <!-- Quick Stats -->
    <div class="stats-row">
      <div class="stat-mini">
        <span class="material-icons">group</span>
        <div class="stat-info">
          <span class="stat-num">{{ stats.teamMembers || 0 }}</span>
          <span class="stat-text">Team</span>
        </div>
      </div>
      <div class="stat-mini">
        <span class="material-icons">pending_actions</span>
        <div class="stat-info">
          <span class="stat-num">{{ stats.pendingRequests || 0 }}</span>
          <span class="stat-text">Pending</span>
        </div>
      </div>
      <div class="stat-mini">
        <span class="material-icons">history</span>
        <div class="stat-info">
          <span class="stat-num">{{ stats.recentActivities || 0 }}</span>
          <span class="stat-text">Activities</span>
        </div>
      </div>
    </div>

    <!-- Section Tabs -->
    <div class="section-tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab.id"
        :class="['section-tab', { active: activeSection === tab.id }]"
        @click="activeSection = tab.id"
      >
        <span class="material-icons">{{ tab.icon }}</span>
        {{ tab.label }}
      </button>
    </div>

    <!-- Section Content -->
    <div class="section-content">
      <!-- Activity Section -->
      <div v-if="activeSection === 'activities'" class="activities-section">
        <div v-if="loading" class="state-message loading">
          <span class="material-icons spin">sync</span> Loading...
        </div>
        <div v-else-if="!activities.length" class="state-message empty">
          <span class="material-icons">inbox</span> No recent activities
        </div>
        <div v-else class="activity-feed">
          <div 
            v-for="item in activities" 
            :key="item.id"
            class="activity-card"
          >
            <div :class="['activity-badge', item.role]">
              <span class="material-icons">{{ getActivityIcon(item.type) }}</span>
            </div>
            <div class="activity-body">
              <div class="activity-head">
                <span class="activity-title">{{ item.title }}</span>
                <span class="activity-time">{{ timeAgo(item.created_at) }}</span>
              </div>
              <p class="activity-desc">{{ item.description }}</p>
              <div class="activity-meta">
                <span :class="['role-tag', item.role]">{{ item.role_label }}</span>
                <span v-if="item.target_class">Class: {{ item.target_class }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Team Section -->
      <div v-if="activeSection === 'team'" class="team-section">
        <div v-if="loading" class="state-message loading">
          <span class="material-icons spin">sync</span> Loading...
        </div>
        <div v-else-if="!teamMembers.length" class="state-message empty">
          <span class="material-icons">group_off</span> No team members
        </div>
        <div v-else class="member-list">
          <div 
            v-for="member in teamMembers" 
            :key="member.id"
            class="member-row"
          >
            <div class="member-img">
              <img v-if="member.avatar" :src="member.avatar" :alt="member.name">
              <span v-else class="material-icons">person</span>
            </div>
            <div class="member-data">
              <div class="member-name">{{ member.name }}</div>
              <div class="member-role">{{ member.role_label }}</div>
            </div>
            <div :class="['member-online', { active: member.is_online }]">
              {{ member.is_online ? 'Online' : 'Offline' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Requests Section -->
      <div v-if="activeSection === 'requests'" class="requests-section">
        <div class="request-header">
          <button class="action-btn primary" @click="openRequestModal">
            <span class="material-icons">add</span>
            New Request
          </button>
        </div>

        <div v-if="loading" class="state-message loading">
          <span class="material-icons spin">sync</span> Loading...
        </div>
        <div v-else-if="!requests.length" class="state-message empty">
          <span class="material-icons">task_alt</span> No pending requests
        </div>
        <div v-else class="request-cards">
          <div 
            v-for="req in requests" 
            :key="req.id"
            class="request-card"
          >
            <div class="req-top">
              <h4 class="req-title">{{ req.title }}</h4>
              <span :class="['req-priority', req.priority]">{{ req.priority }}</span>
            </div>
            <p class="req-desc">{{ req.description }}</p>
            <div class="req-info">
              <span><strong>From:</strong> {{ req.from_name }}</span>
              <span><strong>To:</strong> {{ req.to_role }}</span>
            </div>
            <div class="req-actions">
              <button 
                v-if="req.status === 'pending'"
                class="btn-small success"
                @click="handleRequest(req.id, 'approved')"
              >
                Approve
              </button>
              <button 
                v-if="req.status === 'pending'"
                class="btn-small danger"
                @click="handleRequest(req.id, 'rejected')"
              >
                Reject
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Request Modal -->
    <div v-if="requestModalOpen" class="modal-backdrop" @click.self="requestModalOpen = false">
      <div class="modal-box">
        <div class="modal-title">
          <h3>Create Collaboration Request</h3>
          <button class="close-icon" @click="requestModalOpen = false">
            <span class="material-icons">close</span>
          </button>
        </div>
        <form @submit.prevent="submitNewRequest">
          <div class="form-field">
            <label>Type</label>
            <select v-model="requestForm.type" required>
              <option value="">Select type...</option>
              <option value="support">Support Request</option>
              <option value="coordination">Coordination</option>
              <option value="information">Information</option>
            </select>
          </div>
          <div class="form-field">
            <label>Title</label>
            <input v-model="requestForm.title" type="text" required placeholder="Request title">
          </div>
          <div class="form-field">
            <label>Description</label>
            <textarea v-model="requestForm.description" rows="4" required placeholder="Describe..."></textarea>
          </div>
          <div class="form-field">
            <label>Priority</label>
            <select v-model="requestForm.priority" required>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="modal-actions">
            <button type="button" class="action-btn secondary" @click="requestModalOpen = false">Cancel</button>
            <button type="submit" class="action-btn primary" :disabled="submitting">
              {{ submitting ? 'Sending...' : 'Submit' }}
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

const loading = ref(false)
const submitting = ref(false)
const activeSection = ref('activities')
const requestModalOpen = ref(false)

const tabs = [
  { id: 'activities', label: 'Activities', icon: 'timeline' },
  { id: 'team', label: 'Team', icon: 'groups' },
  { id: 'requests', label: 'Requests', icon: 'pending_actions' }
]

const stats = ref({})
const activities = ref([])
const teamMembers = ref([])
const requests = ref([])

const requestForm = ref({
  type: '',
  title: '',
  description: '',
  priority: 'medium'
})

const fetchAll = async () => {
  loading.value = true
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
    console.error('Load error:', e)
  } finally {
    loading.value = false
  }
}

const openRequestModal = () => {
  requestForm.value = { type: '', title: '', description: '', priority: 'medium' }
  requestModalOpen.value = true
}

const submitNewRequest = async () => {
  submitting.value = true
  try {
    await collaborationService.createRequest(requestForm.value)
    requestModalOpen.value = false
    await fetchAll()
  } catch (e) {
    alert(e.message || 'Submit failed')
  } finally {
    submitting.value = false
  }
}

const handleRequest = async (id, status) => {
  if (!confirm(`Confirm ${status}?`)) return
  try {
    await collaborationService.resolveRequest(id, status)
    await fetchAll()
  } catch (e) {
    alert(e.message || 'Action failed')
  }
}

const getActivityIcon = (type) => {
  const icons = {
    attendance: 'check_circle',
    absence: 'warning',
    followup: 'notifications',
    report: 'assessment',
    default: 'info'
  }
  return icons[type] || icons.default
}

const timeAgo = (dateStr) => {
  if (!dateStr) return ''
  const diff = Date.now() - new Date(dateStr)
  const mins = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  if (mins < 1) return 'Just now'
  if (mins < 60) return `${mins}m ago`
  if (hours < 24) return `${hours}h ago`
  if (days < 7) return `${days}d ago`
  return new Date(dateStr).toLocaleDateString()
}

onMounted(() => {
  fetchAll()
})
</script>

<style scoped>
.collaboration-panel {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  overflow: hidden;
}

.stats-row {
  display: flex;
  gap: 1rem;
  padding: 1.25rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-mini {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: rgba(255,255,255,0.2);
  border-radius: 8px;
}

.stat-mini .material-icons {
  color: white;
  font-size: 1.5rem;
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-num {
  font-size: 1.25rem;
  font-weight: 700;
  color: white;
}

.stat-text {
  font-size: 0.75rem;
  color: rgba(255,255,255,0.8);
}

.section-tabs {
  display: flex;
  padding: 0 1.25rem;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.section-tab {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 1.25rem;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: #6b7280;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.section-tab.active {
  color: #10b981;
  border-bottom-color: #10b981;
}

.section-tab .material-icons {
  font-size: 1.125rem;
}

.section-content {
  padding: 1.25rem;
  min-height: 350px;
}

.state-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #9ca3af;
}

.state-message .material-icons {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.activity-feed {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.activity-card {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 3px solid #10b981;
}

.activity-badge {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: white;
  flex-shrink: 0;
}

.activity-badge.admin { background: #3b82f6; }
.activity-badge.education { background: #10b981; }
.activity-badge.teacher { background: #f59e0b; }

.activity-body {
  flex: 1;
}

.activity-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.25rem;
}

.activity-title {
  font-weight: 600;
  color: #1f2937;
}

.activity-time {
  font-size: 0.75rem;
  color: #9ca3af;
}

.activity-desc {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0.25rem 0;
}

.activity-meta {
  display: flex;
  gap: 0.75rem;
  font-size: 0.75rem;
  color: #9ca3af;
}

.role-tag {
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  text-transform: capitalize;
}

.role-tag.admin { background: #dbeafe; color: #1d4ed8; }
.role-tag.education { background: #d1fae5; color: #047857; }
.role-tag.teacher { background: #fef3c7; color: #b45309; }

.member-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.member-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 8px;
}

.member-img {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.member-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-data {
  flex: 1;
}

.member-name {
  font-weight: 600;
  color: #1f2937;
}

.member-role {
  font-size: 0.875rem;
  color: #6b7280;
}

.member-online {
  font-size: 0.75rem;
  color: #9ca3af;
}

.member-online.active {
  color: #10b981;
}

.request-header {
  margin-bottom: 1rem;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn.primary {
  background: #10b981;
  color: white;
}

.action-btn.primary:hover {
  background: #059669;
}

.action-btn.primary:disabled {
  background: #6ee7b7;
}

.action-btn.secondary {
  background: #f3f4f6;
  color: #374151;
}

.action-btn.secondary:hover {
  background: #e5e7eb;
}

.request-cards {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.request-card {
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
}

.req-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.req-title {
  margin: 0;
  font-size: 1rem;
  color: #1f2937;
}

.req-priority {
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  text-transform: capitalize;
}

.req-priority.low { background: #d1fae5; color: #047857; }
.req-priority.medium { background: #fef3c7; color: #b45309; }
.req-priority.high { background: #fee2e2; color: #dc2626; }
.req-priority.urgent { background: #fee2e2; color: #991b1b; }

.req-desc {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0.5rem 0;
}

.req-info {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #9ca3af;
  margin-bottom: 0.75rem;
}

.req-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-small {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.btn-small.success {
  background: #10b981;
  color: white;
}

.btn-small.success:hover {
  background: #059669;
}

.btn-small.danger {
  background: #ef4444;
  color: white;
}

.btn-small.danger:hover {
  background: #dc2626;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-box {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 480px;
}

.modal-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title h3 {
  margin: 0;
  font-size: 1.125rem;
  color: #1f2937;
}

.close-icon {
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
}

.modal-box form {
  padding: 1.25rem;
}

.form-field {
  margin-bottom: 1rem;
}

.form-field label {
  display: block;
  margin-bottom: 0.375rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.form-field input,
.form-field select,
.form-field textarea {
  width: 100%;
  padding: 0.625rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.9375rem;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus {
  outline: none;
  border-color: #10b981;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.25rem;
}
</style>