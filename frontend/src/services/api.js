import { clearToken, getToken } from './auth';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

const parseResponse = async (response) => {
  const contentType = response.headers.get('content-type') || '';

  if (contentType.includes('application/json')) {
    return response.json();
  }

  return response.text();
};

const request = async (path, options = {}) => {
  const { method = 'GET', body, headers = {} } = options;
  const token = getToken();
  const requestHeaders = {
    Accept: 'application/json',
    ...headers,
  };

  if (token) {
    requestHeaders.Authorization = `Bearer ${token}`;
  }

  let requestBody = body;
  if (body !== undefined && body !== null && !(body instanceof FormData)) {
    requestHeaders['Content-Type'] = 'application/json';
    requestBody = JSON.stringify(body);
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    method,
    headers: requestHeaders,
    body: requestBody,
  });

  const data = await parseResponse(response);

  if (response.status === 401) {
    clearToken();
  }

  if (!response.ok) {
    const message =
      (data && typeof data === 'object' && data.message) ||
      `Request failed with status ${response.status}`;
    const error = new Error(message);
    error.response = { status: response.status, data };
    throw error;
  }

  return { data, status: response.status };
};

const api = {
  get: (path, config = {}) => request(path, { ...config, method: 'GET' }),
  post: (path, body, config = {}) => request(path, { ...config, method: 'POST', body }),
  put: (path, body, config = {}) => request(path, { ...config, method: 'PUT', body }),
  patch: (path, body, config = {}) => request(path, { ...config, method: 'PATCH', body }),
  delete: (path, config = {}) => request(path, { ...config, method: 'DELETE' }),
};

export const fetchAttendanceHistory = async () => {
  const { data } = await api.get('/attendance/history');
  return data;
};

export const checkIn = async (record) => {
  const { data } = await api.post('/attendance/check-in', record);
  return data;
};

export const submitManualAttendanceRequest = async (payload) => {
  const { data } = await api.post('/attendance/manual-request', payload);
  return data;
};

export default api;
