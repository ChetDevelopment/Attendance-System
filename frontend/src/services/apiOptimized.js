/**
 * Optimized API Service for Better Performance
 * 
 * Features:
 * - Request batching
 * - Smart caching with TTL
 * - Request deduplication
 * - Lazy loading support
 */

import api from './api';

// In-memory cache for API responses
const responseCache = new Map();
const pendingRequests = new Map();

/**
 * Get cached data if available and not expired
 */
const getCachedData = (key) => {
  const cached = responseCache.get(key);
  if (!cached) return null;
  
  if (Date.now() > cached.expiry) {
    responseCache.delete(key);
    return null;
  }
  
  return cached.data;
};

/**
 * Set cached data with TTL
 */
const setCachedData = (key, data, ttl = 300000) => {
  responseCache.set(key, {
    data,
    expiry: Date.now() + ttl
  });
};

/**
 * Generate cache key from request params
 */
const generateCacheKey = (url, params = {}) => {
  return `${url}:${JSON.stringify(params)}`;
};

/**
 * Optimized GET request with caching
 */
export const getOptimized = async (url, options = {}) => {
  const { 
    cache = false, 
    cacheTTL = 300000, // 5 minutes default
    params = {} 
  } = options;
  
  const cacheKey = generateCacheKey(url, params);
  
  // Return cached data if available
  if (cache) {
    const cachedData = getCachedData(cacheKey);
    if (cachedData) {
      return cachedData;
    }
  }
  
  try {
    const response = await api.get(url, { params });
    
    // Cache the response if caching is enabled
    if (cache) {
      setCachedData(cacheKey, response.data, cacheTTL);
    }
    
    return response.data;
  } catch (error) {
    console.error(`API Error [${url}]:`, error);
    throw error;
  }
};

/**
 * Batch multiple requests together
 * Returns array of responses in same order as requests
 */
export const batch = async (requests) => {
  const promises = requests.map(([url, options = {}]) => {
    return getOptimized(url, options);
  });
  
  return Promise.all(promises);
};

/**
 * Prefetch data in background
 */
export const prefetch = (url, options = {}) => {
  const { cacheTTL = 300000, params = {} } = options;
  const cacheKey = generateCacheKey(url, params);
  
  // Don't prefetch if already cached
  if (getCachedData(cacheKey)) {
    return Promise.resolve(getCachedData(cacheKey));
  }

  if (pendingRequests.has(cacheKey)) {
    return pendingRequests.get(cacheKey);
  }
  
  // Prefetch in background
  const request = api.get(url, { params })
    .then(response => {
      setCachedData(cacheKey, response.data, cacheTTL);
      return response.data;
    })
    .catch(error => {
      console.error(`Prefetch Error [${url}]:`, error);
      return null;
    })
    .finally(() => {
      pendingRequests.delete(cacheKey);
    });

  pendingRequests.set(cacheKey, request);
    
  return request;
};

/**
 * Deduplicate requests - prevents multiple identical requests
 */
export const getDeduplicated = async (url, options = {}) => {
  const cacheKey = generateCacheKey(url, options.params);
  
  // Check if request is already pending
  if (pendingRequests.has(cacheKey)) {
    return pendingRequests.get(cacheKey);
  }
  
  // Create new request
  const request = getOptimized(url, options)
    .finally(() => {
      // Remove from pending after completion
      pendingRequests.delete(cacheKey);
    });
  
  // Store pending request
  pendingRequests.set(cacheKey, request);
  
  return request;
};

/**
 * Clear specific cache or all caches
 */
export const clearCache = (url = null) => {
  if (url) {
    [...responseCache.keys()].forEach((cacheKey) => {
      if (cacheKey.startsWith(`${url}:`)) {
        responseCache.delete(cacheKey);
      }
    });
  } else {
    responseCache.clear();
  }
};

/**
 * Load dashboard data efficiently - batches common requests
 */
export const loadDashboardData = async () => {
  return batch([
    ['/education-dashboard/stats', { cache: true, cacheTTL: 300000 }],
    ['/education-dashboard/absent-today', { cache: true, cacheTTL: 60000 }],
    ['/sessions/active', { cache: true, cacheTTL: 30000 }],
  ]);
};

/**
 * Load student data efficiently
 */
export const loadStudentData = async (studentId) => {
  return batch([
    [`/reports/student/${studentId}`, { cache: true, cacheTTL: 300000 }],
    [`/student/attendance/history?limit=50`, { cache: true, cacheTTL: 60000 }],
  ]);
};

/**
 * Load class reports efficiently
 */
export const loadClassReport = async (classId, month, year) => {
  return batch([
    [`/reports/class/${classId}/month/${month}/year/${year}`, { cache: true, cacheTTL: 300000 }],
  ]);
};

export default {
  getOptimized,
  batch,
  prefetch,
  getDeduplicated,
  clearCache,
  loadDashboardData,
  loadStudentData,
  loadClassReport,
};
