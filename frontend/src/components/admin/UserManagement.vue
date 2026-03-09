<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Modal from './Modal.vue';
import { Search, Key, Power, UserPlus, CheckCircle } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import api from '../../services/api';

type RoleItem = { id: number; name: string; slug: string };
type UserItem = { id: number; name: string; role: string; roleId: number | null; email: string; status: 'Active' | 'Inactive' };

const users = ref<UserItem[]>([]);
const roles = ref<RoleItem[]>([]);
const isCreateModalOpen = ref(false);
const isResetModalOpen = ref(false);
const selectedUser = ref<any>(null);
const resetSuccess = ref(false);
const isLoadingUsers = ref(false);
const isCreatingUser = ref(false);
const errorMessage = ref('');
const searchQuery = ref('');
const roleFilter = ref('All Roles');
const newUser = ref({
  id: '', 
  title: 'Mr.', 
  name: '', 
  department: 'Science', 
  email: '', 
  username: '', 
  password: '', 
  roleId: null as number | null,
});

const mapUser = (u: any): UserItem => ({
  id: Number(u.id),
  name: String(u.name ?? ''),
  role: String(u.role?.name ?? u.roles?.[0]?.name ?? 'Unknown'),
  roleId: u.role_id ?? u.role?.id ?? u.roles?.[0]?.id ?? null,
  email: String(u.email ?? ''),
  status: u.is_active ? 'Active' : 'Inactive',
});

const loadRoles = async () => {
  const { data } = await api.get('/admin/roles');
  roles.value = Array.isArray(data) ? data : [];

  if (!newUser.value.roleId) {
    const preferred = roles.value.find((r) => r.slug === 'teacher') ?? roles.value[0];
    newUser.value.roleId = preferred ? preferred.id : null;
  }
};

const loadUsers = async () => {
  isLoadingUsers.value = true;
  errorMessage.value = '';
  try {
    const { data } = await api.get('/admin/users');
    users.value = Array.isArray(data) ? data.map(mapUser) : [];
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to load users.';
  } finally {
    isLoadingUsers.value = false;
  }
};

const toggleStatus = async (id: number) => {
  const current = users.value.find((u) => u.id === id);
  if (!current) return;

  const nextActive = current.status !== 'Active';
  await api.put(`/admin/users/${id}`, { is_active: nextActive });
  users.value = users.value.map((u) => (u.id === id ? { ...u, status: nextActive ? 'Active' : 'Inactive' } : u));
};

const handleCreateUser = async () => {
  if (!newUser.value.name || !newUser.value.email || !newUser.value.password || !newUser.value.roleId) {
    errorMessage.value = 'Please fill in Name, Email, Password, and User Type.';
    return;
  }

  isCreatingUser.value = true;
  errorMessage.value = '';
  try {
    const payload = {
      name: `${newUser.value.title} ${newUser.value.name}`.trim(),
      email: newUser.value.email.trim(),
      password: newUser.value.password,
      role_id: newUser.value.roleId,
      is_active: true,
    };

    const { data } = await api.post('/admin/users', payload);
    users.value.push(mapUser(data));
    isCreateModalOpen.value = false;
    newUser.value = {
      id: '', 
      title: 'Mr.', 
      name: '', 
      department: 'Science', 
      email: '', 
      username: '', 
      password: '', 
      roleId: roles.value.find((r) => r.slug === 'teacher')?.id ?? roles.value[0]?.id ?? null,
    };
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to create user.';
  } finally {
    isCreatingUser.value = false;
  }
};

const handleResetPassword = () => {
  // Simulate API call
  setTimeout(() => {
    resetSuccess.value = true;
    setTimeout(() => {
      isResetModalOpen.value = false;
      resetSuccess.value = false;
      selectedUser.value = null;
    }, 2000);
  }, 1000);
};

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const matchesSearch = user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                         user.email.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesRole = roleFilter.value === 'All Roles' || user.role === roleFilter.value;
    return matchesSearch && matchesRole;
  });
});

onMounted(async () => {
  await loadRoles();
  await loadUsers();
});
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">User & Permission</h2>
        <p class="text-sm text-slate-500 font-medium">Manage staff accounts and access levels</p>
      </div>
      <button 
        @click="isCreateModalOpen = true"
        class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all"
      >
        <UserPlus class="size-4" />
        Create Staff
      </button>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="errorMessage" class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
        {{ errorMessage }}
      </div>
      <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <div class="relative max-w-xs w-full">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
          <input 
            type="text" 
            placeholder="Search users..." 
            v-model="searchQuery"
            class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
          />
        </div>
        <div class="flex items-center gap-2">
          <select 
            v-model="roleFilter"
            class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none"
          >
            <option>All Roles</option>
            <option>Admin</option>
            <option>Teacher</option>
            <option>Staff</option>
          </select>
        </div>
      </div>
      <div v-if="isLoadingUsers" class="px-6 py-6 text-sm text-slate-500">Loading users...</div>

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
          <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4">
              <div class="font-bold text-slate-900">{{ user.name }}</div>
              <div class="text-[10px] text-slate-400">{{ user.email }}</div>
            </td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded uppercase">
                {{ user.role }}
              </span>
            </td>
            <td class="px-6 py-4">
              <span :class="cn(
                'px-2 py-1 text-[10px] font-black rounded uppercase',
                user.status === 'Active' ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-400'
              )">
                {{ user.status }}
              </span>
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
                  @click="toggleStatus(user.id)"
                  :class="cn(
                    'p-2 rounded-lg transition-all',
                    user.status === 'Active' ? 'text-red-400 hover:bg-red-50' : 'text-green-400 hover:bg-green-50'
                  )"
                  :title="user.status === 'Active' ? 'Deactivate' : 'Activate'"
                >
                  <Power class="size-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredUsers.length === 0">
            <td colSpan="4" class="px-6 py-10 text-center text-slate-400 italic">No users found matching your criteria.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Modal 
      :is-open="isCreateModalOpen" 
      @close="isCreateModalOpen = false" 
      title="AdminLecturer - Staff Management"
      size="lg"
    >
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="space-y-6 border-r border-slate-100 pr-8">
          <div class="relative">
            <span class="absolute -top-3 left-4 bg-white px-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Create Lecturer</span>
            <div class="border border-slate-200 rounded-xl p-6 pt-8 space-y-4">
              <div class="grid grid-cols-3 items-center gap-4">
                <label class="text-xs font-bold text-slate-600">Lecturer ID</label>
                <input 
                  type="text" 
                  v-model="newUser.id"
                  class="col-span-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20" 
                />
              </div>
              <div class="grid grid-cols-3 items-center gap-4">
                <label class="text-xs font-bold text-slate-600">Title</label>
                <select 
                  v-model="newUser.title"
                  class="col-span-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20"
                >
                  <option>Mr.</option>
                  <option>Ms.</option>
                  <option>Dr.</option>
                  <option>Prof.</option>
                </select>
              </div>
              <div class="grid grid-cols-3 items-center gap-4">
                <label class="text-xs font-bold text-slate-600">Name</label>
                <input 
                  type="text" 
                  v-model="newUser.name"
                  class="col-span-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20" 
                />
              </div>
              <div class="grid grid-cols-3 items-center gap-4">
                <label class="text-xs font-bold text-slate-600">Department</label>
                <select 
                  v-model="newUser.department"
                  class="col-span-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20"
                >
                  <option>Science</option>
                  <option>Arts</option>
                  <option>Engineering</option>
                  <option>Medicine</option>
                </select>
              </div>
              <div class="grid grid-cols-3 items-center gap-4">
                <label class="text-xs font-bold text-slate-600">Email</label>
                <input 
                  type="email" 
                  v-model="newUser.email"
                  class="col-span-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20" 
                />
              </div>
              <div class="grid grid-cols-3 items-center gap-4">
                <label class="text-xs font-bold text-slate-600">Username</label>
                <input 
                  type="text" 
                  v-model="newUser.username"
                  class="col-span-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20" 
                />
              </div>
              <div class="grid grid-cols-3 items-center gap-4">
                <label class="text-xs font-bold text-slate-600">Password</label>
                <input 
                  type="password" 
                  v-model="newUser.password"
                  class="col-span-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20" 
                />
              </div>
              <div class="grid grid-cols-3 items-center gap-4">
                <label class="text-xs font-bold text-slate-600">User Type</label>
                <select 
                  v-model="newUser.roleId"
                  class="col-span-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-sm outline-none focus:ring-2 focus:ring-primary/20"
                >
                  <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                </select>
              </div>
            </div>
          </div>

          <div class="flex gap-3">
            <button 
              @click="handleCreateUser"
              :disabled="isCreatingUser"
              class="flex-1 py-2 bg-white border border-slate-300 rounded shadow-sm text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all"
            >
              {{ isCreatingUser ? 'Saving...' : 'Save' }}
            </button>
            <button class="flex-1 py-2 bg-white border border-slate-300 rounded shadow-sm text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all">
              Update
            </button>
            <button 
              @click="isCreateModalOpen = false"
              class="flex-1 py-2 bg-white border border-slate-300 rounded shadow-sm text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all"
            >
              Cancel
            </button>
          </div>
        </div>

        <div class="flex flex-col justify-between py-4">
          <div class="flex flex-col items-center space-y-4">
            <div class="size-40 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-center p-4">
              <div class="text-center">
                <div class="size-16 bg-indigo-600 rounded-full mx-auto mb-2 flex items-center justify-center text-white font-black text-xl">U</div>
                <div class="text-[10px] font-black text-slate-800 uppercase leading-tight">University of Excellence</div>
                <div class="text-[8px] font-bold text-slate-400 mt-1 italic">FOR TRUTH & SERVICE</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Modal>

    <Modal 
      :is-open="isResetModalOpen" 
      @close="isResetModalOpen = false" 
      title="Reset User Password"
      size="sm"
    >
      <div class="space-y-6 py-4">
        <template v-if="!resetSuccess">
          <div class="flex flex-col items-center text-center space-y-3">
            <div class="size-16 bg-primary/10 rounded-full flex items-center justify-center">
              <Key class="size-8 text-primary" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900">Reset Password?</h3>
              <p class="text-sm text-slate-500">
                Are you sure you want to reset the password for <span class="font-bold text-slate-900">{{ selectedUser?.name }}</span>?
              </p>
            </div>
          </div>
          <div class="flex flex-col gap-2">
            <button 
              @click="handleResetPassword"
              class="w-full py-3 bg-primary text-white rounded-xl font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all"
            >
              Confirm Reset
            </button>
            <button 
              @click="isResetModalOpen = false"
              class="w-full py-3 bg-slate-100 text-slate-600 rounded-xl font-bold text-sm hover:bg-slate-200 transition-all"
            >
              Cancel
            </button>
          </div>
        </template>
        <div v-else class="flex flex-col items-center text-center space-y-4 py-8">
          <div class="size-16 bg-green-100 rounded-full flex items-center justify-center">
            <CheckCircle class="size-8 text-green-600" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900">Password Reset!</h3>
            <p class="text-sm text-slate-500">
              A temporary password has been sent to <br />
              <span class="font-bold text-slate-900">{{ selectedUser?.email }}</span>
            </p>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>
