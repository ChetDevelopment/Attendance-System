<template>
  <div class="collaboration-panel">
    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon bg-blue">
          <span class="material-icons">group</span>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.teamMembers || 0 }}</div>
          <div class="stat-label">Team Members</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon bg-green">
          <span class="material-icons">pending_actions</span>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.pendingRequests || 0 }}</div>
          <div class="stat-label">Pending Requests</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon bg-purple">
          <span class="material-icons">notifications</span>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.recentActivities || 0 }}</div>
          <div class="stat-label">Recent Activities</div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="panel-tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab.id"
        :class="['tab-btn', { active: activeTab === tab.id }]"
        @click="activeTab = tab.id"
      >
        <span class="material-icons">{{ tab.icon }}</span>
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab Content -->
    <div class="panel-content">
      <!-- Activity Feed Tab -->
      <div v-if="activeTab === 'activity'" class="activity-tab">
        <div v-if="loading" class="loading-state">
          <span class="material-icons spin">sync</span>
          Loading activities...
        </div>
        <div v-else-if="activities.length === 0" class="empty-state">
          <span class="material-icons">inbox</span>
          <p>No recent activities</p>
        </div>
        <div v-else class="activity-list">
          <div 
            v-for="activity in activities" 
            :key="activity.id"
            class="activity-item"
          >
            <div :class="['activity-icon', `bg-${activity.role_color || 'gray'}`]">
              <span class="material-icons">{{ activity.icon || 'info' }}</span>
            </div>
            <div class="activity-details">
              <div class="activity-title">{{ activity.title }}</div>
              <div class="activity-meta">
                <span class="role-badge" :class="activity.role">{{ activity.role_label }}</span>
                <span class="activity-time">{{ formatTime(activity.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Team Members Tab -->
      <div v-if="activeTab === 'team'" class="team-tab">
        <div v-if="loading" class="loading-state">
          <span class="material-icons spin">sync</span>
          Loading team...
        </div>
        <div v-else-if="teamMembers.length === 0" class="empty-state">
          <span class="material-icons">group_off</span>
          <p>No team members found</p>
        </div>
        <div v-else class="team-grid">
          <div 
            v-for="member in teamMembers" 
            :key="member.id"
            class="team-card"
          >
            <div class="member-avatar">
              <img v-if="member.avatar" :src="member.avatar" :alt="member.name">
              <span v-else class="material-icons">person</span>
            </div>
            <div class="member-info">
              <div class="member-name">{{ member.name }}</div>
              <div class="member-role">{{ member.role_label }}</div>
              <div class="member-status" :class="{ online: member.is_online }">
                {{ member.is_online ? 'Online' : 'Offline' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Requests Tab -->
      <div v-if="activeTab === 'requests'" class="requests-tab">
        <div class="request-actions">
          <button class="btn btn-primary" @click="showRequestForm = true">
            <span class="material-icons">add</span>
            New Request
          </button>
        </div>
        
        <div v-if="loading" class="loading-state">
          <span class="material-icons spin">sync</span>
          Loading requests...
        </div>
        <div v-else-if="requests.length === 0" class="empty-state">
          <span class="material-icons">check_circle</span>
          <p>No pending requests</p>
        </div>
        <div v-else class="requests-list">
          <div 
            v-for="request in requests" 
            :key="request.id"
            class="request-item"
          >
            <div class="request-header">
              <div class="request-title">{{ request.title }}</div>
              <span :class="['priority-badge', request.priority]">{{ request.priority }}</span>
            </div>
            <div class="request-description">{{ request.description }}</div>
            <div class="request-meta">
              <span>From: {{ request.from_name }}</span>
              <span>To: {{ request.to_role }}</span>
            </div>
            <div class="request-actions">
              <button 
                v-if="request.status === 'pending'"
                class="btn btn-sm btn-success"
                @click="resolveRequest(request.id, 'approved')"
              >
                Approve
              </button>
              <button 
                v-if="request.status === 'pending'"
                class="btn btn-sm btn-danger"
                @click="resolveRequest(request.id, 'rejected')"
              >
                Reject
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New Request Modal -->
    <div v-if="showRequestForm" class="modal-overlay" @click.self="showRequestForm = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Create New Request</h3>
          <button class="close-btn" @click="showRequestForm = false">
            <span class="material-icons">close</span>
          </button>
        </div>
        <form @submit.prevent="submitRequest">
          <div class="form-group">
            <label>Request Type</label>
            <select v-model="newRequest.type" required>
              <option value="">Select type...</option>
              <option value="support">Support Request</option>
              <option value="coordination">Coordination</option>
              <option value="information">Information Request</option>
            </select>
          </div>
          <div class="form-group">
            <label>Title</label>
            <input v-model="newRequest.title" type="text" required placeholder="Request title">
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="newRequest.description" rows="4" required placeholder="Describe your request..."></textarea>
          </div>
          <div class="form-group">
            <label>Priority</label>
            <select v-model="newRequest.priority" required>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="showRequestForm = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Submitting...' : 'Submit Request' }}
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
const activeTab = ref('activity')
const showRequestForm = ref(false)

const tabs = [
  { id: 'activity', label: 'Activity Feed', icon: 'timeline' },
  { id: 'team', label: 'Team Members', icon: 'groups' },
  { id: 'requests', label: 'Requests', icon: 'pending_actions' }
]

const stats = ref({})
const activities = ref([])
const teamMembers = ref([])
const requests = ref([])

const newRequest = ref({
  type: '',
  title: '',
  description: '',
  priority: 'medium'
})

const loadData = async () => {
  loading.value = true
  try {
    const [statsData, activitiesData, teamData, requestsData] = await Promise.all([
      collaborationService.getQuickStats(),
      collaborationService.getActivityFeed({ limit: 10 }),
      collaborationService.getTeamMembers(),
      collaborationService.getPendingRequests()
    ])
    stats.value = statsData
    activities.value = activitiesData
    teamMembers.value = teamData
    requests.value = requestsData
  } catch (error) {
    console.error('Failed to load collaboration data:', error)
  } finally {
    loading.value = false
  }
}

const submitRequest = async () => {
  submitting.value = true
  try {
    await collaborationService.createRequest(newRequest.value)
    showRequestForm.value = false
    newRequest.value = { type: '', title: '', description: '', priority: 'medium' }
    await loadData()
  } catch (error) {
    alert(error.message || 'Failed to submit request')
  } finally {
    submitting.value = false
  }
}

const resolveRequest = async (id, status) => {
  if (!confirm(`Are you sure you want to ${status} this request?`)) return
  try {
    await collaborationService.resolveRequest(id, status)
    await loadData()
  } catch (error) {
    alert(error.message || 'Failed to resolve request')
  }
}

const formatTime = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diff = now - date
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  
  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  if (hours < 24) return `${hours}h ago`
  if (days < 7) return `${days}d ago`
  return date.toLocaleDateString()
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.collaboration-panel {
  padding: 1.5rem;
  background: var(--card-bg, #fff);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  color: white;
}

.stat-icon.bg-blue { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-icon.bg-green { background: linear-gradient(135deg, #11998e, #38ef7d); }
.stat-icon.bg-purple { background: linear-gradient(135deg, #a855f7, #6366f1); }

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.panel-tabs {
  display: flex;
  gap: 0.5rem;
  border-bottom: 2px solid #e5e7eb;
  margin-bottom: 1rem;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  color: #6b7280;
  font-weight: 500;
  transition: all 0.2s;
}

.tab-btn.active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.tab-btn:hover {
  color: #3b82f6;
}

.tab-btn .material-icons {
  font-size: 1.25rem;
}

.panel-content {
  min-height: 300px;
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #9ca3af;
}

.loading-state .material-icons,
.empty-state .material-icons {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding: 0.75rem;
  border-radius: 8px;
  background: #f9fafb;
  transition: background 0.2s;
}

.activity-item:hover {
  background: #f3f4f6;
}

.activity-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: white;
  flex-shrink: 0;
}

.activity-icon.bg-admin { background: #3b82f6; }
.activity-icon.bg-education { background: #10b981; }
.activity-icon.bg-teacher { background: #f59e0b; }
.activity-icon.bg-gray { background: #6b7280; }

.activity-title {
  font-weight: 500;
  color: #1f2937;
}

.activity-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.role-badge {
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  text-transform: capitalize;
}

.role-badge.admin { background: #dbeafe; color: #1d4ed8; }
.role-badge.education { background: #d1fae5; color: #047857; }
.role-badge.teacher { background: #fef3c7; color: #b45309; }

.activity-time {
  font-size: 0.75rem;
  color: #9ca3af;
}

.team-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.team-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 8px;
  text-align: center;
}

.member-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.75rem;
  overflow: hidden;
}

.member-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-name {
  font-weight: 600;
  color: #1f2937;
}

.member-role {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.member-status {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-top: 0.25rem;
}

.member-status.online {
  color: #10b981;
}

.request-actions {
  margin-bottom: 1rem;
}

.requests-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.request-item {
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
}

.request-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.request-title {
  font-weight: 600;
  color: #1f2937;
}

.priority-badge {
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  text-transform: capitalize;
}

.priority-badge.low { background: #d1fae5; color: #047857; }
.priority-badge.medium { background: #fef3c7; color: #b45309; }
.priority-badge.high { background: #fee2e2; color: #dc2626; }
.priority-badge.urgent { background: #fee2e2; color: #991b1b; }

.request-description {
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.request-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #9ca3af;
  margin-bottom: 0.75rem;
}

.request-item .request-actions {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
}

.modal-content form {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3b82f6;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-primary:disabled {
  background: #93c5fd;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover {
  background: #059669;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
}
</style>