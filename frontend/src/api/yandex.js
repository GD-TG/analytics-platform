import api from './http.js';

export const yandexApi = {
  // New auth URL (per-user)
  getAuthUrl: (redirectUri) => api.get('/api/yandex/auth-url-new', { redirect_uri: redirectUri }).then(res => res),

  // Exchange code for tokens (server will create YandexAccount)
  exchangeCode: (code, redirectUri) => api.post('/api/yandex/exchange-code-new', { code, redirect_uri: redirectUri }),

  // List counters for an account
  listCounters: (accountId) => api.get('/api/yandex/counters', { account_id: accountId }),

  // Save selected counters
  saveCounters: (payload) => api.post('/api/yandex/counters/save', payload),
};
