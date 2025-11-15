const API_BASE = 'http://localhost:8000/api';

const getAuthToken = () => {
  return localStorage.getItem('auth_token') || '';
};

const apiCall = async (method, endpoint, data = null, headers = {}) => {
  const token = getAuthToken();
  const config = {
    method,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` }),
      ...headers,
    },
  };

  if (data) {
    config.body = JSON.stringify(data);
  }

  try {
    const response = await fetch(`${API_BASE}${endpoint}`, config);
    const result = await response.json();
    return result;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
};

// Auth API
export const auth = {
  register: (data) => apiCall('POST', '/auth/register', data),
  login: (data) => apiCall('POST', '/auth/login', data),
  logout: () => apiCall('POST', '/auth/logout'),
  getMe: () => apiCall('GET', '/auth/me'),
  getYandexUrl: (redirectUri) => apiCall('GET', `/auth/yandex/url?redirect_uri=${encodeURIComponent(redirectUri)}`),
  yandexAuth: (code, redirectUri) => apiCall('POST', '/auth/yandex', { code, redirect_uri: redirectUri }),
  yandexCallback: (code, state, redirectUri) => apiCall('POST', '/auth/yandex/callback', { code, state, redirect_uri: redirectUri }),
};

// Projects API
export const projects = {
  list: () => apiCall('GET', '/projects'),
  get: (id) => apiCall('GET', `/projects/${id}`),
  create: (data) => apiCall('POST', '/projects', data),
  update: (id, data) => apiCall('PUT', `/projects/${id}`, data),
  delete: (id) => apiCall('DELETE', `/projects/${id}`),
};

// Counters API
export const counters = {
  list: (projectId) => apiCall('GET', `/projects/${projectId}/counters`),
  create: (projectId, data) => apiCall('POST', `/projects/${projectId}/counters`, data),
  delete: (projectId, counterId) => apiCall('DELETE', `/projects/${projectId}/counters/${counterId}`),
};

// Direct Accounts API
export const directAccounts = {
  list: (projectId) => apiCall('GET', `/projects/${projectId}/direct-accounts`),
  create: (projectId, data) => apiCall('POST', `/projects/${projectId}/direct-accounts`, data),
  delete: (projectId, accountId) => apiCall('DELETE', `/projects/${projectId}/direct-accounts/${accountId}`),
};

// Goals API
export const goals = {
  list: (projectId) => apiCall('GET', `/projects/${projectId}/goals`),
  create: (projectId, data) => apiCall('POST', `/projects/${projectId}/goals`, data),
  update: (projectId, goalId, data) => apiCall('PUT', `/projects/${projectId}/goals/${goalId}`, data),
  delete: (projectId, goalId) => apiCall('DELETE', `/projects/${projectId}/goals/${goalId}`),
};

// Sync API
export const sync = {
  trigger: (projectId) => apiCall('POST', `/projects/${projectId}/sync`),
  status: (projectId) => apiCall('GET', `/projects/${projectId}/sync/status`),
};

// Report API
export const report = {
  get: (projectId) => apiCall('GET', `/projects/${projectId}/report`),
};

// Settings API
export const settings = {
  get: () => apiCall('GET', '/settings'),
  updateYandexMetrika: (data) => apiCall('POST', '/settings/yandex-metrika', data),
  updateYandexDirect: (data) => apiCall('POST', '/settings/yandex-direct', data),
  updateSyncSettings: (data) => apiCall('POST', '/settings/sync', data),
  testYandexMetrika: () => apiCall('POST', '/settings/test/yandex-metrika'),
  testYandexDirect: () => apiCall('POST', '/settings/test/yandex-direct'),
};

// Dashboard API
export const dashboard = {
  syncStatus: () => apiCall('GET', '/dashboard/sync-status'),
  stats: () => apiCall('GET', '/dashboard/stats'),
  recentSyncs: () => apiCall('GET', '/dashboard/recent-syncs'),
};
