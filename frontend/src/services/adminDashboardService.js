/**
 * Admin Dashboard Service
 * Optimized for fast response (< 1 second)
 * Uses caching and single endpoint for all data
 */

import { getOptimized, clearCache } from './apiOptimized'

const BASE_URL = '/admin/dashboard'

/**
 * Get complete dashboard data in one request
 * Uses backend caching (30s) for fast response
 */
export const getDashboardData = async (options = {}) => {
  const { 
    bypassCache = false, 
    cacheTTL = 30000 // 30 seconds - matches backend cache
  } = options

  return getOptimized(BASE_URL, {
    cache: !bypassCache,
    cacheTTL
  })
}

/**
 * Get quick stats for widget updates
 */
export const getQuickStats = async (options = {}) => {
  const { bypassCache = false, cacheTTL = 60000 } = options
  
  return getOptimized(`${BASE_URL}/quick-stats`, {
    cache: !bypassCache,
    cacheTTL
  })
}

/**
 * Get student analytics data
 */
export const getStudentAnalytics = async (options = {}) => {
  const { bypassCache = false, cacheTTL = 120000 } = options
  
  return getOptimized(`${BASE_URL}/student-analytics`, {
    cache: !bypassCache,
    cacheTTL
  })
}

/**
 * Get class analytics data
 */
export const getClassAnalytics = async (options = {}) => {
  const { bypassCache = false, cacheTTL = 120000 } = options
  
  return getOptimized(`${BASE_URL}/class-analytics`, {
    cache: !bypassCache,
    cacheTTL
  })
}

/**
 * Get system statistics
 */
export const getSystemStats = async (options = {}) => {
  const { bypassCache = false, cacheTTL = 300000 } = options
  
  return getOptimized(`${BASE_URL}/system-stats`, {
    cache: !bypassCache,
    cacheTTL
  })
}

/**
 * Refresh dashboard data (bypass cache)
 */
export const refreshDashboard = async () => {
  return getDashboardData({ bypassCache: true })
}

/**
 * Clear all cached dashboard data
 */
export const clearDashboardCache = () => {
  clearCache(BASE_URL)
}

export default {
  getDashboardData,
  getQuickStats,
  getStudentAnalytics,
  getClassAnalytics,
  getSystemStats,
  refreshDashboard,
  clearDashboardCache
}
