import api from './http.js';

export const authApi = {
  // Регистрация
  register: (data) => {
    return api.post('/api/auth/register', data);
  },

  // Вход
  login: (data) => {
    return api.post('/api/auth/login', data);
  },

  // Выход
  logout: () => {
    return api.post('/api/auth/logout');
  },

  // Получить текущего пользователя
  me: () => {
    return api.get('/api/auth/me');
  },

  // Получить URL для авторизации через Yandex
  getYandexAuthUrl: (redirectUri) => {
    return api.get('/api/auth/yandex/url', { redirect_uri: redirectUri });
  },

  // Авторизация через Yandex
  yandexAuth: (code, redirectUri) => {
    return api.post('/api/auth/yandex', {
      code,
      redirect_uri: redirectUri,
    });
  },
};

