<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { 
  Fingerprint, 
  CreditCard, 
  Loader2, 
  CheckCircle, 
  XCircle, 
  AlertCircle,
  RefreshCw,
  Clock,
  User,
  Badge,
  Eye,
  EyeOff
} from 'lucide-vue-next'
import { getBiometricHistory, getBiometricStatus, getStudentInfoAfterScan, scanCard, scanFingerprint } from '../../services/studentPortalService'

// State management
const scanType = ref<'card' | 'fingerprint'>('card')
const isScanning = ref(false)
const scanStatus = ref<'idle' | 'scanning' | 'success' | 'error'>('idle')
const errorMessage = ref('')
const successMessage = ref('')
const lastScanTime = ref<Date | null>(null)

// Student info
const studentInfo = ref<{
  id: string
  name: string
  class: string
  enrollmentDate: string
} | null>(null)

// Scan data
const cardData = ref('')
const fingerprintData = ref('')
const showFingerprintPreview = ref(false)
const systemStatus = ref<any>(null)
const recentScans = ref<any[]>([])

// Configuration
const debounceTime = 2000 // 2 seconds between scans
const lastScanTimestamp = ref<number>(0)

// Computed properties
const canScan = computed(() => {
  const now = Date.now()
  return now - lastScanTimestamp.value > debounceTime
})

const statusColor = computed(() => {
  switch (scanStatus.value) {
    case 'scanning': return 'text-blue-600'
    case 'success': return 'text-green-600'
    case 'error': return 'text-red-600'
    default: return 'text-slate-600'
  }
})

const statusIcon = computed(() => {
  switch (scanStatus.value) {
    case 'scanning': return Loader2
    case 'success': return CheckCircle
    case 'error': return XCircle
    default: return AlertCircle
  }
})

// Instructions based on scan type
const getInstructions = computed(() => {
  if (scanType.value === 'card') {
    return [
      'Place your RFID/NFC card near the reader',
      'Wait for the green light confirmation',
      'Do not move the card during scanning'
    ]
  } else {
    return [
      'Place your finger on the fingerprint sensor',
      'Apply gentle pressure and hold steady',
      'Ensure your finger is clean and dry'
    ]
  }
})

// Scan handlers
const handleCardScan = async () => {
  if (!canScan.value) {
    errorMessage.value = 'Please wait before scanning again'
    return
  }

  if (!cardData.value.trim()) {
    errorMessage.value = 'Please enter card data or use the card reader'
    return
  }

  await performScan('card', cardData.value)
}

const handleFingerprintScan = async () => {
  if (!canScan.value) {
    errorMessage.value = 'Please wait before scanning again'
    return
  }

  if (!fingerprintData.value) {
    errorMessage.value = 'Please place your finger on the sensor'
    return
  }

  await performScan('fingerprint', fingerprintData.value)
}

const loadBiometricContext = async () => {
  const [status, history] = await Promise.all([
    getBiometricStatus(),
    getBiometricHistory(),
  ])

  systemStatus.value = status
  recentScans.value = Array.isArray(history) ? history : []
}

const performScan = async (type: 'card' | 'fingerprint', data: string) => {
  isScanning.value = true
  scanStatus.value = 'scanning'
  errorMessage.value = ''
  successMessage.value = ''
  studentInfo.value = null

  try {
    if (!data || data.length < 3) {
      throw new Error('Invalid scan data')
    }

    const sessionId = getCurrentSessionId()
    if (!sessionId) {
      throw new Error('No active session found')
    }

    const result = type === 'card'
      ? await scanCard({ sessionId, cardData: data })
      : await scanFingerprint({ sessionId, fingerprintData: data })

    scanStatus.value = 'success'
    successMessage.value = result?.message || `${type === 'card' ? 'Card' : 'Fingerprint'} scan successful!`
    lastScanTime.value = new Date()
    lastScanTimestamp.value = Date.now()
    
    const resolvedStudentInfo = result?.student || await getStudentInfoAfterScan({ scanType: type, scanData: data })
    studentInfo.value = resolvedStudentInfo
    await loadBiometricContext()

    setTimeout(() => {
      resetScan()
    }, 3000)

  } catch (error: any) {
    scanStatus.value = 'error'
    lastScanTimestamp.value = Date.now()
    
    const message = error?.response?.data?.message || error?.message || 'Scan failed. Please try again.'

    if (message.includes('Invalid card')) {
      errorMessage.value = 'Invalid card. Please try again.'
    } else if (message.includes('Fingerprint mismatch')) {
      errorMessage.value = 'Fingerprint mismatch. Please try again.'
    } else if (message.includes('not enrolled')) {
      errorMessage.value = `${type === 'card' ? 'Card' : 'Fingerprint'} not enrolled. Please contact administrator.`
    } else {
      errorMessage.value = message
    }

    setTimeout(() => {
      resetScan()
    }, 4000)
  } finally {
    isScanning.value = false
  }
}

const resetScan = () => {
  scanStatus.value = 'idle'
  errorMessage.value = ''
  successMessage.value = ''
  studentInfo.value = null
  cardData.value = ''
  fingerprintData.value = ''
}

const getCurrentSessionId = () => {
  return systemStatus.value?.currentSession?.id || null
}

const simulateFingerprintScan = () => {
  if (scanType.value === 'fingerprint') {
    fingerprintData.value = `fingerprint_${Math.random().toString(36).substr(2, 9)}`
    handleFingerprintScan()
  }
}

const simulateCardScan = () => {
  if (scanType.value === 'card') {
    cardData.value = `card_${Math.random().toString(36).substr(2, 9)}`
    handleCardScan()
  }
}

// Keyboard shortcuts
const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' && canScan.value) {
    if (scanType.value === 'card') {
      handleCardScan()
    } else {
      handleFingerprintScan()
    }
  } else if (event.key === 'c') {
    scanType.value = 'card'
  } else if (event.key === 'f') {
    scanType.value = 'fingerprint'
  } else if (event.key === 'Escape') {
    resetScan()
  }
}

onMounted(() => {
  loadBiometricContext().catch((error) => {
    console.error('Failed to load biometric context:', error)
    errorMessage.value = 'Unable to load biometric status right now.'
  })
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 p-6">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-slate-800 dark:text-white mb-2">
          Biometric Attendance System
        </h1>
        <p class="text-slate-600 dark:text-slate-300">
          Scan your card or fingerprint to mark attendance
        </p>
      </div>

      <!-- Main Card -->
      <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        
        <!-- Scan Type Selector -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-6">
          <div class="flex items-center justify-center space-x-8">
            <button
              @click="scanType = 'card'"
              :class="[
                'flex items-center space-x-3 px-6 py-3 rounded-xl font-semibold transition-all',
                scanType === 'card' 
                  ? 'bg-white text-blue-600 shadow-lg' 
                  : 'bg-white/20 text-white hover:bg-white/30'
              ]"
            >
              <CreditCard :size="24" />
              <span>Card Scan</span>
            </button>
            
            <button
              @click="scanType = 'fingerprint'"
              :class="[
                'flex items-center space-x-3 px-6 py-3 rounded-xl font-semibold transition-all',
                scanType === 'fingerprint' 
                  ? 'bg-white text-purple-600 shadow-lg' 
                  : 'bg-white/20 text-white hover:bg-white/30'
              ]"
            >
              <Fingerprint :size="24" />
              <span>Fingerprint</span>
            </button>
          </div>
        </div>

        <!-- Content Area -->
        <div class="p-8">
          <div class="grid lg:grid-cols-2 gap-8">
            
            <!-- Left Column: Scanner Interface -->
            <div class="space-y-6">
              <!-- Status Display -->
              <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-6 border border-slate-200 dark:border-slate-600">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-200">
                    {{ scanType === 'card' ? 'Card Scanner' : 'Fingerprint Scanner' }}
                  </h3>
                  <div class="flex items-center space-x-2">
                    <component 
                      :is="statusIcon" 
                      :class="[
                        'w-6 h-6',
                        statusColor,
                        scanStatus === 'scanning' ? 'animate-spin' : ''
                      ]"
                    />
                    <span :class="['font-medium', statusColor]">
                      {{ scanStatus === 'idle' ? 'Ready' : scanStatus }}
                    </span>
                  </div>
                </div>

                <!-- Scanner Visual -->
                <div class="relative bg-white dark:bg-slate-800 rounded-xl p-8 border-2 border-dashed border-slate-300 dark:border-slate-600 min-h-48 flex items-center justify-center">
                  
                  <!-- Scanning Animation -->
                  <div v-if="scanStatus === 'scanning'" class="absolute inset-0 pointer-events-none">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 to-purple-400 animate-pulse"></div>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 to-purple-400 animate-pulse" style="animation-delay: 0.5s"></div>
                  </div>

                  <!-- Success Animation -->
                  <div v-else-if="scanStatus === 'success'" class="text-center">
                    <CheckCircle class="w-16 h-16 text-green-500 mx-auto mb-4 animate-bounce" />
                    <p class="text-green-600 font-semibold">{{ successMessage }}</p>
                  </div>

                  <!-- Error Animation -->
                  <div v-else-if="scanStatus === 'error'" class="text-center">
                    <XCircle class="w-16 h-16 text-red-500 mx-auto mb-4 animate-pulse" />
                    <p class="text-red-600 font-semibold">{{ errorMessage }}</p>
                  </div>

                  <!-- Idle State -->
                  <div v-else class="text-center space-y-4">
                    <div class="w-24 h-24 mx-auto bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center">
                      <component 
                        :is="scanType === 'card' ? CreditCard : Fingerprint" 
                        class="w-12 h-12 text-slate-400 dark:text-slate-300"
                      />
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">
                      {{ scanType === 'card' ? 'Ready to scan card' : 'Ready to scan fingerprint' }}
                    </p>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex gap-3">
                  <button
                    @click="scanType === 'card' ? handleCardScan() : handleFingerprintScan()"
                    :disabled="isScanning || !canScan"
                    class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 disabled:from-slate-300 disabled:to-slate-400 text-white font-semibold py-3 px-6 rounded-xl transition-all disabled:cursor-not-allowed"
                  >
                    <span v-if="isScanning">
                      <Loader2 class="animate-spin inline mr-2" :size="18" />
                      Scanning...
                    </span>
                    <span v-else-if="!canScan">
                      <Clock class="inline mr-2" :size="18" />
                      Please wait...
                    </span>
                    <span v-else>
                      {{ scanType === 'card' ? 'Scan Card' : 'Scan Fingerprint' }}
                    </span>
                  </button>
                  
                  <button
                    @click="resetScan"
                    class="px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                  >
                    <RefreshCw :size="18" />
                  </button>
                </div>

                <!-- Input Fields -->
                <div v-if="scanType === 'card'" class="mt-4">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Card Data (Manual Entry)
                  </label>
                  <input
                    v-model="cardData"
                    type="text"
                    placeholder="Enter card ID or use card reader..."
                    class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                  />
                </div>

                <div v-else class="mt-4 space-y-3">
                  <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                      Fingerprint Preview
                    </label>
                    <button
                      @click="showFingerprintPreview = !showFingerprintPreview"
                      class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                    >
                      {{ showFingerprintPreview ? 'Hide' : 'Show' }}
                    </button>
                  </div>
                  
                  <div v-if="showFingerprintPreview" class="bg-slate-100 dark:bg-slate-700 rounded-xl p-4">
                    <div class="grid grid-cols-5 gap-2 opacity-60">
                      <div v-for="i in 20" :key="i" class="h-2 bg-slate-400 dark:bg-slate-500 rounded"></div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Fingerprint pattern simulation</p>
                  </div>
                  
                  <button
                    @click="simulateFingerprintScan"
                    class="w-full py-3 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold rounded-xl transition-all"
                  >
                    Simulate Fingerprint Scan
                  </button>
                </div>
              </div>
            </div>

            <!-- Right Column: Instructions & Student Info -->
            <div class="space-y-6">
              
              <!-- Instructions -->
              <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-700 rounded-2xl p-6 border border-slate-200 dark:border-slate-600">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                  <AlertCircle class="text-blue-600" :size="20" />
                  How to Scan
                </h3>
                <ul class="space-y-3">
                  <li v-for="(instruction, index) in getInstructions" :key="index" class="flex items-start gap-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                    <span class="text-slate-700 dark:text-slate-300">{{ instruction }}</span>
                  </li>
                </ul>
                
                <div class="mt-4 p-4 bg-white dark:bg-slate-700 rounded-xl">
                  <h4 class="font-semibold text-slate-800 dark:text-slate-200 mb-2">Tips:</h4>
                  <ul class="text-sm text-slate-600 dark:text-slate-300 space-y-1">
                    <li>• Ensure good lighting for fingerprint scans</li>
                    <li>• Clean the scanner surface regularly</li>
                    <li>• Hold steady during the scan process</li>
                    <li>• Contact admin if you encounter persistent issues</li>
                  </ul>
                </div>
              </div>

              <!-- Student Information -->
              <div v-if="studentInfo" class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-6 border border-green-200 dark:border-green-800">
                <h3 class="text-lg font-semibold text-green-800 dark:text-green-300 mb-4 flex items-center gap-2">
                  <User class="text-green-600" :size="20" />
                  Student Information
                </h3>
                
                <div class="grid grid-cols-2 gap-4">
                  <div class="bg-white dark:bg-slate-700 rounded-lg p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Student ID</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200 mt-1">{{ studentInfo.id }}</p>
                  </div>
                  
                  <div class="bg-white dark:bg-slate-700 rounded-lg p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Name</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200 mt-1">{{ studentInfo.name }}</p>
                  </div>
                  
                  <div class="bg-white dark:bg-slate-700 rounded-lg p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Class</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200 mt-1">{{ studentInfo.class }}</p>
                  </div>
                  
                  <div class="bg-white dark:bg-slate-700 rounded-lg p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Enrollment Date</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200 mt-1">{{ studentInfo.enrollmentDate }}</p>
                  </div>
                </div>

                <div v-if="lastScanTime" class="mt-4 flex items-center justify-between text-sm text-slate-600 dark:text-slate-300">
                  <span>Scan completed at: {{ lastScanTime.toLocaleTimeString() }}</span>
                  <Badge class="text-green-600" :size="18" />
                </div>
              </div>

              <!-- Error Display -->
              <div v-if="errorMessage && scanStatus === 'error'" class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-6 border border-red-200 dark:border-red-800">
                <h3 class="text-lg font-semibold text-red-800 dark:text-red-300 mb-2 flex items-center gap-2">
                  <XCircle class="text-red-600" :size="20" />
                  Scan Error
                </h3>
                <p class="text-red-700 dark:text-red-300">{{ errorMessage }}</p>
                <div class="mt-4 flex gap-3">
                  <button
                    @click="scanType === 'card' ? handleCardScan() : handleFingerprintScan()"
                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"
                  >
                    Try Again
                  </button>
                  <button
                    @click="resetScan"
                    class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                  >
                    Reset
                  </button>
                </div>
              </div>

              <!-- System Status -->
              <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-6 border border-slate-200 dark:border-slate-600">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-4">System Status</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-300">Scanner Status</span>
                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs">{{ systemStatus?.scanner || 'Unknown' }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-300">Connection</span>
                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs">{{ systemStatus?.connection || 'Unknown' }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-300">Last Scan</span>
                    <span class="text-slate-700 dark:text-slate-200 font-medium">
                      {{ lastScanTime ? lastScanTime.toLocaleString() : 'None' }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-300">Next Scan Ready</span>
                    <span class="text-slate-700 dark:text-slate-200 font-medium">
                      {{ canScan ? 'Now' : 'In ' + Math.ceil((debounceTime - (Date.now() - lastScanTimestamp.value)) / 1000) + 's' }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between col-span-2">
                    <span class="text-slate-600 dark:text-slate-300">Active Session</span>
                    <span class="text-slate-700 dark:text-slate-200 font-medium">
                      {{ systemStatus?.currentSession?.name || 'No active session' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center mt-8 text-slate-500 dark:text-slate-400 text-sm">
        <p>Keyboard shortcuts: C (Card), F (Fingerprint), Enter (Scan), Esc (Reset)</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes scanLine {
  0% { transform: translateY(-100%); }
  100% { transform: translateY(100%); }
}

.animate-scan-line {
  animation: scanLine 2s linear infinite;
}
</style>
