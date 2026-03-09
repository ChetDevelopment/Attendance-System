import { clearToken, getToken } from './auth';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

type RequestOptions = {
  method?: string;
  body?: unknown;
  headers?: Record<string, string>;
};

const parseResponse = async (response: Response): Promise<any> => {
  const contentType = response.headers.get('content-type') || '';

  if (contentType.includes('application/json')) {
    return response.json();
  }

  return response.text();
};

const request = async (path: string, options: RequestOptions = {}): Promise<{ data: any; status: number }> => {
  const { method = 'GET', body, headers = {} } = options;
  const token = getToken();
  const requestHeaders: Record<string, string> = {
    Accept: 'application/json',
    ...headers,
  };

  if (token) {
    requestHeaders.Authorization = `Bearer ${token}`;
  }

  let requestBody: BodyInit | undefined;
  if (body !== undefined && body !== null) {
    if (body instanceof FormData) {
      requestBody = body;
    } else {
      requestHeaders['Content-Type'] = 'application/json';
      requestBody = JSON.stringify(body);
    }
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
      (data && typeof data === 'object' && 'message' in data ? (data as any).message : null) ||
      `Request failed with status ${response.status}`;
    const error = new Error(String(message)) as Error & { response?: { status: number; data: any } };
    error.response = { status: response.status, data };
    throw error;
  }

  return { data, status: response.status };
};

const api = {
  get: (path: string, config: RequestOptions = {}) => request(path, { ...config, method: 'GET' }),
  post: (path: string, body: unknown, config: RequestOptions = {}) => request(path, { ...config, method: 'POST', body }),
  put: (path: string, body: unknown, config: RequestOptions = {}) => request(path, { ...config, method: 'PUT', body }),
  patch: (path: string, body: unknown, config: RequestOptions = {}) => request(path, { ...config, method: 'PATCH', body }),
  delete: (path: string, config: RequestOptions = {}) => request(path, { ...config, method: 'DELETE' }),
};

export const fetchAttendanceHistory = async (): Promise<any> => {
  const { data } = await api.get('/attendance/history');
  return data;
};

export const checkIn = async (record: unknown): Promise<any> => {
  const { data } = await api.post('/attendance/check-in', record);
  return data;
};

export const submitManualAttendanceRequest = async (payload: unknown): Promise<any> => {
  const { data } = await api.post('/attendance/manual-request', payload);
  return data;
};

export default api;
