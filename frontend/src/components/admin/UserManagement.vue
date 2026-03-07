<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Modal from './Modal.vue'
import ConfirmationModal from './ConfirmationModal.vue'
import { Search, Key, Power, UserPlus, CheckCircle } from 'lucide-vue-next'
import { userService } from '../../services/userService'

type RoleType = {
  id: number
  name: string
  description?: string
}

type UserType = {
  id: number
  name: string
  email: string
  role_id: number
  role?: RoleType
}

const users = ref<UserType[]>([])
const roles = ref<RoleType[]>([])
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const validationErrors = ref<Record<string, string[]>>({})

const isCreateModalOpen = ref(false)
const isResetModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const selectedUser = ref<UserType | null>(null)
const userToDelete = ref<UserType | null>(null)
const resetSuccess = ref(false)
const searchQuery = ref('')
const roleFilter = ref('all')
const editingUserId = ref<number | null>(null)

const form = ref({
  name: '',
  email: '',
  password: '',
  role_id: '',
})

const loadData = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [usersData, rolesData] = await Promise.all([userService.getUsers(), userService.getRoles()])
    users.value = Array.isArray(usersData) ? usersData : []
    roles.value = Array.isArray(rolesData) ? rolesData : []
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load users.'
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = { name: '', email: '', password: '', role_id: '' }
  editingUserId.value = null
  validationErrors.value = {}
}

const openCreate = () => {
  resetForm()
  isCreateModalOpen.value = true
}

const openEdit = (user: UserType) => {
  form.value = {
    name: user.name || '',
    email: user.email || '',
    password: '',
    role_id: String(user.role_id || user.role?.id || ''),
  }
  editingUserId.value = user.id
  validationErrors.value = {}
  isCreateModalOpen.value = true
}

const submitUser = async () => {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ''
  validationErrors.value = {}

  const payload: Record<string, any> = {
    name: form.value.name,
    email: form.value.email,
    role_id: Number(form.value.role_id),
  }

  if (!editingUserId.value || form.value.password) {
    payload.password = form.value.password
  }

  try {
    if (editingUserId.value) {
      await userService.updateUser(editingUserId.value, payload)
    } else {
      await userService.createUser(payload)
    }
    isCreateModalOpen.value = false
    resetForm()
    await loadData()
  } catch (error: any) {
    errorMessage.value = error.message || 'Unable to save user.'
    validationErrors.value = error.errors || {}
  } finally {
    saving.value = false
  }
}

const deleteUser = async (id: number) => {
  try {
    await userService.deleteUser(id)
    await loadData()
  } catch (error: any) {
    errorMessage.value = error.message || 'Unable to delete user.'
  }
}

const openDeleteModal = (user: UserType) => {
  userToDelete.value = user
  isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false
  userToDelete.value = null
}

const confirmDeleteUser = async () => {
  if (!userToDelete.value) return
  
  const id = userToDelete.value.id
  closeDeleteModal()
  
  try {
    await userService.deleteUser(id)
    await loadData()
  } catch (error: any) {
    errorMessage.value = error.message || 'Unable to delete user.'
  }
}

const handleResetPassword = () => {
  // Placeholder action for reset flow in UI; backend reset endpoint is not currently available.
  resetSuccess.value = true
  setTimeout(() => {
    isResetModalOpen.value = false
    resetSuccess.value = false
    selectedUser.value = null
  }, 1500)
}

const filteredUsers = computed(() =>
  users.value.filter((user) => {
    const q = searchQuery.value.toLowerCase()
    const bySearch =
      String(user.name || '').toLowerCase().includes(q) ||
      String(user.email || '').toLowerCase().includes(q)
    const byRole =
      roleFilter.value === 'all' || String(user.role?.id || user.role_id) === String(roleFilter.value)
    return bySearch && byRole
  })
)

onMounted(loadData)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">User & Permission</h2>
        <p class="text-sm text-slate-500 font-medium">Manage staff accounts and access levels</p>
      </div>
      <button
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all"
      >
        <UserPlus class="size-4" />
        Create Staff
      </button>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <div class="relative max-w-xs w-full">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search users..."
            class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
          />
        </div>
        <div class="flex items-center gap-2">
          <select v-model="roleFilter" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none">
            <option value="all">All Roles</option>
            <option v-for="role in roles" :key="role.id" :value="String(role.id)">{{ role.name }}</option>
          </select>
          <button class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-bold" :disabled="loading" @click="loadData">
            Refresh
          </button>
        </div>
      </div>

      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider">
          <tr>
            <th class="px-6 py-4">User</th>
            <th class="px-6 py-4">Role</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td :colspan="4" class="px-6 py-10 text-center text-slate-400 italic">Loading users...</td>
          </tr>
          <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4">
              <div class="font-bold text-slate-900">{{ user.name }}</div>
              <div class="text-[10px] text-slate-400">{{ user.email }}</div>
            </td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded uppercase">
                {{ user.role?.name || user.role_id }}
              </span>
            </td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 text-[10px] font-black rounded uppercase bg-green-100 text-green-600">Active</span>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="selectedUser = user; isResetModalOpen = true"
                  class="p-2 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-all"
                  title="Reset Password"
                >
                  <Key class="size-4" />
                </button>
                <button
                  @click="openEdit(user)"
                  class="p-2 rounded-lg transition-all text-sky-500 hover:bg-sky-50"
                  title="Edit"
                >
                  <Power class="size-4" />
                </button>
                <button
                  @click="openDeleteModal(user)"
                  class="p-2 rounded-lg transition-all text-red-500 hover:bg-red-50"
                  title="Delete"
                >
                  <Power class="size-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && filteredUsers.length === 0">
            <td :colspan="4" class="px-6 py-10 text-center text-slate-400 italic">No users found matching your criteria.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Modal :is-open="isCreateModalOpen" :title="editingUserId ? 'Edit Staff' : 'Create Staff'" size="md" @close="isCreateModalOpen = false">
      <div class="space-y-4">
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase">Name</label>
          <input v-model="form.name" type="text" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20" />
          <p v-if="validationErrors.name" class="text-xs text-red-500 mt-1">{{ validationErrors.name[0] }}</p>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase">Email</label>
          <input v-model="form.email" type="email" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20" />
          <p v-if="validationErrors.email" class="text-xs text-red-500 mt-1">{{ validationErrors.email[0] }}</p>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase">
            {{ editingUserId ? 'New Password (optional)' : 'Password' }}
          </label>
          <input v-model="form.password" type="password" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20" />
          <p v-if="validationErrors.password" class="text-xs text-red-500 mt-1">{{ validationErrors.password[0] }}</p>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase">Role</label>
          <select v-model="form.role_id" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20">
            <option value="">Select Role</option>
            <option v-for="role in roles" :key="role.id" :value="String(role.id)">{{ role.name }}</option>
          </select>
          <p v-if="validationErrors.role_id" class="text-xs text-red-500 mt-1">{{ validationErrors.role_id[0] }}</p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button @click="isCreateModalOpen = false" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-bold">Cancel</button>
          <button @click="submitUser" :disabled="saving" class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-bold disabled:opacity-60">
            {{ saving ? 'Saving...' : editingUserId ? 'Update' : 'Create' }}
          </button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="isResetModalOpen" title="Reset User Password" size="sm" @close="isResetModalOpen = false">
      <div class="space-y-6 py-4">
        <div v-if="!resetSuccess">
          <div class="flex flex-col items-center text-center space-y-3">
            <div class="size-16 bg-primary/10 rounded-full flex items-center justify-center">
              <Key class="size-8 text-primary" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900">Reset Password?</h3>
              <p class="text-sm text-slate-500">
                Confirm password reset flow for
                <span class="font-bold text-slate-900"> {{ selectedUser?.name }}</span>.
              </p>
            </div>
          </div>
          <div class="flex flex-col gap-2 mt-4">
            <button @click="handleResetPassword" class="w-full py-3 bg-primary text-white rounded-xl font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">Confirm Reset</button>
            <button @click="isResetModalOpen = false" class="w-full py-3 bg-slate-100 text-slate-600 rounded-xl font-bold text-sm hover:bg-slate-200 transition-all">Cancel</button>
          </div>
        </div>
        <div v-else class="flex flex-col items-center text-center space-y-4 py-8">
          <div class="size-16 bg-green-100 rounded-full flex items-center justify-center">
            <CheckCircle class="size-8 text-green-600" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900">Password Reset Triggered</h3>
            <p class="text-sm text-slate-500">
              Reset action completed for<br />
              <span class="font-bold text-slate-900">{{ selectedUser?.email }}</span>
            </p>
          </div>
        </div>
      </div>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      :is-open="isDeleteModalOpen"
      title="Delete User"
      :message="`Are you sure you want to delete ${userToDelete?.name || 'this user'}? This action cannot be undone.`"
      confirm-text="Delete"
      cancel-text="Cancel"
      variant="danger"
      @confirm="confirmDeleteUser"
      @cancel="closeDeleteModal"
    />
  </div>
</template>
